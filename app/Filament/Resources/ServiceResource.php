<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    use \App\Filament\Concerns\HasResourcePermissions;

    protected static ?string $model = Service::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 1;
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Service Name (e.g. Strength Class)'),
                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'live_in_person' => 'Live (In-person)',
                                'live_online' => 'Live (Online - Zoom/Meet)',
                                'recorded' => 'Recorded (On-demand Video)',
                            ])
                            ->label('Service Type'),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->numeric()
                            ->label('Duration (minutes)')
                            ->default(60),
                        Forms\Components\TextInput::make('stripe_payment_link')
                            ->url()
                            ->maxLength(255)
                            ->label('Stripe Payment Link (Drop-in Pass)'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                    ])->columns(2),
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
                        Tables\Columns\BadgeColumn::make('type')
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'live_in_person' => 'Live (In-Person)',
                                'live_online' => 'Live (Online)',
                                'recorded' => 'On-Demand',
                                default => $state,
                            })
                            ->colors([
                                'primary' => 'live_in_person',
                                'success' => 'live_online',
                                'warning' => 'recorded',
                            ]),
                        Tables\Columns\TextColumn::make('name')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                            ->searchable(),
                        Tables\Columns\TextColumn::make('duration_minutes')
                            ->icon('heroicon-m-clock')
                            ->suffix(' minutes')
                            ->color('gray'),
                        Tables\Columns\TextColumn::make('description')
                            ->color('gray')
                            ->limit(120),
                    ])->space(2),
                ])
                ->actions([
                    Tables\Actions\Action::make('book_drop_in')
                        ->label('Book Drop-in Pass')
                        ->icon('heroicon-m-ticket')
                        ->button()
                        ->color('primary')
                        ->url(fn (Service $record): ?string => $record->stripe_payment_link, shouldOpenInNewTab: true)
                        ->visible(fn (Service $record): bool => filled($record->stripe_payment_link)),
                ])
                ->bulkActions([]);
        }

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'primary' => 'live_in_person',
                        'success' => 'live_online',
                        'warning' => 'recorded',
                    ]),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label('Duration')
                    ->suffix(' min'),
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
