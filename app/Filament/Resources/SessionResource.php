<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SessionResource\Pages;
use App\Filament\Resources\SessionResource\RelationManagers;
use App\Models\Booking;
use App\Models\Session;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SessionResource extends Resource
{
    use \App\Filament\Concerns\HasResourcePermissions;

    protected static ?string $model = Session::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Agenda Session';
    protected static bool $shouldRegisterNavigation = false;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['service', 'plans', 'bookings']);

        if (static::isSubscriber()) {
            $planId = auth()->user()?->plan_id;
            $query->where(function (\Illuminate\Database\Eloquent\Builder $q) use ($planId) {
                $q->whereDoesntHave('plans');
                if ($planId) {
                    $q->orWhereHas('plans', fn (\Illuminate\Database\Eloquent\Builder $p) => $p->where('plans.id', $planId));
                }
                // Also allow sessions that have a drop-in link so subscribers without the plan can drop in
                $q->orWhereNotNull('stripe_payment_link');
            });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Session Details')
                    ->description('Specify class name, service type, and scheduling.')
                    ->schema([
                        Forms\Components\Select::make('event_type')
                            ->required()
                            ->options([
                                'live_in_person' => 'Live (In-person)',
                                'live_online' => 'Live Online',
                                'recorded' => 'Recorded (On-demand)',
                            ])
                            ->default('live_in_person')
                            ->label('Event Type'),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->label('Event / Class Title')
                            ->placeholder('e.g., Morning Strength Training - Group A'),
                        Forms\Components\DateTimePicker::make('start_time')
                            ->required()
                            ->label('Start Date & Time'),
                        Forms\Components\DateTimePicker::make('end_time')
                            ->required()
                            ->label('End Date & Time')
                            ->after('start_time'),
                        Forms\Components\TextInput::make('location_or_link')
                            ->label('Gym Location / Room / Link')
                            ->placeholder('e.g., Studio 1 (Gym), Zoom link, or Physical address')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Capacity & Drop-in Pricing')
                    ->description('Set maximum gym capacity and drop-in pricing for single-class passes.')
                    ->schema([
                        Forms\Components\TextInput::make('max_seats')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(10)
                            ->label('Max Capacity (Seats / Lugares)')
                            ->helperText('Maximum number of participants allowed in this session (e.g. 10 gym spots).'),
                        Forms\Components\TextInput::make('drop_in_price')
                            ->numeric()
                            ->prefix('$')
                            ->label('Drop-in Price (USD)')
                            ->helperText('One-time price for non-members / single pass booking.'),
                        Forms\Components\TextInput::make('stripe_payment_link')
                            ->url()
                            ->maxLength(500)
                            ->label('Stripe Drop-in Payment Link')
                            ->placeholder('https://buy.stripe.com/...')
                            ->helperText('Direct Stripe payment link for drop-in clients to purchase this single session.')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Recurrence Schedule')
                    ->description('Configure repeating session occurrences (Daily, Weekly, Every 2 Weeks, Every 4 Weeks, Monthly).')
                    ->schema([
                        Forms\Components\Toggle::make('is_recurring')
                            ->label('Repeat this session (Sesión recurrente)')
                            ->helperText('When enabled, upcoming occurrences will be automatically scheduled.')
                            ->reactive(),
                        Forms\Components\Select::make('recurrence_rule')
                            ->options([
                                'daily' => 'Daily (Diaria)',
                                'weekly' => 'Weekly - Every 7 days (Semanal)',
                                'biweekly' => 'Every 2 Weeks - 14 days (Cada dos semanas)',
                                'every_4_weeks' => 'Every 4 Weeks - 28 days (Cada cuatro semanas)',
                                'monthly' => 'Monthly (Mensual)',
                            ])
                            ->label('Recurrence Pattern (Frecuencia)')
                            ->visible(fn (Forms\Get $get) => (bool) $get('is_recurring'))
                            ->required(fn (Forms\Get $get) => (bool) $get('is_recurring')),
                        Forms\Components\DatePicker::make('recurrence_end_date')
                            ->label('Repeat Until (Fecha límite de repetición)')
                            ->helperText('Occurrences will be generated up to this date (default: 3 months from start date, max 52 occurrences).')
                            ->visible(fn (Forms\Get $get) => (bool) $get('is_recurring'))
                            ->afterOrEqual('start_time'),
                    ])->columns(3),

                Forms\Components\Section::make('Membership Access')
                    ->schema([
                        Forms\Components\Select::make('plans')
                            ->multiple()
                            ->relationship('plans', 'name_en')
                            ->label('Included in Memberships (Plans)')
                            ->helperText('Members active on these plans can reserve a spot without paying extra. If empty, all active members can book.')
                            ->columnSpanFull()
                            ->preload(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (static::isSubscriber()) {
            return $table
                ->contentGrid([
                    'md' => 2,
                    'xl' => 3,
                ])
                ->columns([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('service.name')
                                ->badge()
                                ->color('primary'),
                            Tables\Columns\TextColumn::make('seats_badge')
                                ->state(fn (Session $record): string => $record->is_full ? 'FULL (0 spots left)' : "{$record->available_seats} / {$record->max_seats} spots available")
                                ->badge()
                                ->color(fn (Session $record): string => $record->is_full ? 'danger' : ($record->available_seats <= 2 ? 'warning' : 'success')),
                        ]),
                        Tables\Columns\TextColumn::make('title')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                            ->searchable(),
                        Tables\Columns\TextColumn::make('start_time')
                            ->dateTime('M d, Y · g:i A')
                            ->icon('heroicon-m-calendar')
                            ->color('gray'),
                        Tables\Columns\TextColumn::make('location_or_link')
                            ->icon('heroicon-m-map-pin')
                            ->color('gray')
                            ->limit(45),
                    ])->space(2),
                ])
                ->defaultSort('start_time', 'asc')
                ->actions([
                    Tables\Actions\Action::make('book')
                        ->label(function (Session $record): string {
                            if (Booking::where('user_id', auth()->id())->where('session_id', $record->id)->exists()) {
                                return 'Booked ✓';
                            }
                            if ($record->is_full) {
                                return 'Session Full';
                            }
                            return 'Reserve Spot';
                        })
                        ->icon('heroicon-m-check-circle')
                        ->button()
                        ->color(function (Session $record): string {
                            if (Booking::where('user_id', auth()->id())->where('session_id', $record->id)->exists()) {
                                return 'success';
                            }
                            if ($record->is_full) {
                                return 'gray';
                            }
                            return 'primary';
                        })
                        ->disabled(function (Session $record): bool {
                            return Booking::where('user_id', auth()->id())->where('session_id', $record->id)->exists() || $record->is_full;
                        })
                        ->action(function (Session $record) {
                            if ($record->is_full) {
                                Notification::make()
                                    ->danger()
                                    ->title('Session is Full')
                                    ->body('Sorry, all spots for this session have been booked.')
                                    ->send();
                                return;
                            }

                            Booking::firstOrCreate(
                                ['user_id' => auth()->id(), 'session_id' => $record->id],
                                ['status' => 'paid', 'amount_paid' => 0]
                            );

                            Notification::make()
                                ->success()
                                ->title('Spot reserved successfully!')
                                ->body("You're booked for {$record->title}.")
                                ->send();
                        }),
                    Tables\Actions\Action::make('drop_in')
                        ->label(fn (Session $record): string => 'Drop-in ($' . number_format($record->drop_in_price ?? 0, 2) . ')')
                        ->icon('heroicon-m-credit-card')
                        ->color('info')
                        ->url(fn (Session $record): ?string => $record->stripe_payment_link, shouldOpenInNewTab: true)
                        ->visible(fn (Session $record): bool => filled($record->stripe_payment_link) && !Booking::where('user_id', auth()->id())->where('session_id', $record->id)->exists()),
                    Tables\Actions\Action::make('join')
                        ->label('Open Link')
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->color('gray')
                        ->url(fn (Session $record): ?string => filter_var($record->location_or_link, FILTER_VALIDATE_URL) ? $record->location_or_link : null, shouldOpenInNewTab: true)
                        ->visible(fn (Session $record): bool => (bool) filter_var($record->location_or_link, FILTER_VALIDATE_URL)),
                ])
                ->bulkActions([]);
        }

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->dateTime('M d, Y · g:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('capacity')
                    ->label('Occupancy / Seats')
                    ->state(fn (Session $record): string => "{$record->booked_seats_count} / {$record->max_seats} seats")
                    ->badge()
                    ->color(fn (Session $record): string => $record->is_full ? 'danger' : ($record->available_seats <= 2 ? 'warning' : 'success'))
                    ->icon(fn (Session $record): string => $record->is_full ? 'heroicon-m-x-circle' : 'heroicon-m-users'),
                Tables\Columns\TextColumn::make('recurrence_rule')
                    ->label('Recurrence')
                    ->badge()
                    ->formatStateUsing(fn (?string $state, Session $record): string => match($state) {
                        'daily' => 'Daily',
                        'weekly' => 'Weekly (7d)',
                        'biweekly' => 'Biweekly (14d)',
                        'every_4_weeks' => 'Every 4 Weeks (28d)',
                        'monthly' => 'Monthly',
                        default => $record->parent_session_id ? 'Recurring Instance' : 'One-time',
                    })
                    ->color(fn (?string $state, Session $record): string => $record->is_recurring || $record->parent_session_id ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('drop_in_price')
                    ->money('usd')
                    ->label('Drop-in Price')
                    ->sortable(),
                Tables\Columns\TextColumn::make('plans.name_en')
                    ->label('Memberships')
                    ->listWithLineBreaks()
                    ->limitList(2),
            ])
            ->filters([
                //
            ])
            ->defaultGroup(
                Tables\Grouping\Group::make('start_time')
                    ->label('Date')
                    ->date()
                    ->collapsible()
            )
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSessions::route('/'),
            'create' => Pages\CreateSession::route('/create'),
            'edit' => Pages\EditSession::route('/{record}/edit'),
        ];
    }
}
