<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    use \App\Filament\Concerns\HasResourcePermissions;

    protected static ?string $model = Booking::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Drop-in Booking';
    protected static ?string $pluralModelLabel = 'Drop-in Bookings';
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return static::isSubscriber() ? 'My Bookings' : 'Drop-in Bookings';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery()->with(['session.service']);

        if (static::isSubscriber()) {
            $query->where('user_id', auth()->id());
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Client'),
                        Forms\Components\Select::make('session_id')
                            ->relationship('session', 'title')
                            ->getOptionLabelFromRecordUsing(fn (\App\Models\Session $record) => "{$record->title} (" . ($record->start_time?->format('M d, g:i A') ?? 'No date') . " · {$record->available_seats}/{$record->max_seats} spots)")
                            ->required()
                            ->searchable()
                            ->preload()
                            ->label('Class / Session'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending Payment',
                                'paid' => 'Paid',
                                'attended' => 'Attended',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('paid'),
                        Forms\Components\TextInput::make('amount_paid')
                            ->numeric()
                            ->prefix('$')
                            ->label('Amount Paid'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        if (static::isSubscriber()) {
            return $table
                ->columns([
                    Tables\Columns\TextColumn::make('session.title')
                        ->label('Class / Session')
                        ->weight(\Filament\Support\Enums\FontWeight::Bold)
                        ->searchable(),
                    Tables\Columns\TextColumn::make('session.service.name')
                        ->label('Service')
                        ->badge()
                        ->color('primary'),
                    Tables\Columns\TextColumn::make('session.start_time')
                        ->label('Date & Time')
                        ->dateTime('M d, Y · g:i A')
                        ->sortable(),
                    Tables\Columns\BadgeColumn::make('status')
                        ->colors([
                            'warning' => 'pending',
                            'success' => fn ($state) => in_array($state, ['paid', 'attended', 'confirmed']),
                            'danger' => 'cancelled',
                        ]),
                ])
                ->actions([
                    Tables\Actions\Action::make('join')
                        ->label('Join Session')
                        ->icon('heroicon-m-video-camera')
                        ->button()
                        ->color('primary')
                        ->url(fn (Booking $record): ?string => filter_var($record->session?->location_or_link, FILTER_VALIDATE_URL) ? $record->session->location_or_link : null, shouldOpenInNewTab: true)
                        ->visible(fn (Booking $record): bool => (bool) filter_var($record->session?->location_or_link, FILTER_VALIDATE_URL)),
                ])
                ->bulkActions([]);
        }

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('session.title')
                    ->label('Session')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => fn ($state) => in_array($state, ['paid', 'attended']),
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('amount_paid')
                    ->money('usd')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
