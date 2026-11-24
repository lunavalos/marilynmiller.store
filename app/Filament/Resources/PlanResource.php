<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Models\Plan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    use \App\Filament\Concerns\HasResourcePermissions;

    protected static ?string $model = Plan::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        if (static::isSubscriber()) {
            return false;
        }

        $user = auth()->user();
        if (!$user) {
            return false;
        }
        if ($user->hasRole('super_admin')) {
            return true;
        }

        $slug = static::getPermissionSlug();
        return $user->can("view_any_{$slug}") || $user->can("view_{$slug}");
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name_en')
                            ->required()
                            ->maxLength(255)
                            ->label('Name (EN)'),
                        Forms\Components\TextInput::make('name_es')
                            ->required()
                            ->maxLength(255)
                            ->label('Name (ES)'),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('$')
                            ->label('Price (USD)'),
                        Forms\Components\Select::make('billing_cycle')
                            ->required()
                            ->options([
                                'one-time' => 'One-Time',
                                'every_4_weeks' => 'Every 4 Weeks (28 days)',
                                'monthly' => 'Monthly',
                                'annual' => 'Annual',
                            ])
                            ->default('monthly')
                            ->label('Billing Cycle'),
                        Forms\Components\TextInput::make('stripe_payment_link')
                            ->url()
                            ->maxLength(255)
                            ->label('Stripe Payment Link (buy.stripe.com/...)'),
                    ])->columns(2),

                Forms\Components\Section::make('Descriptions')
                    ->schema([
                        Forms\Components\Textarea::make('description_en')
                            ->label('Description (EN)'),
                        Forms\Components\Textarea::make('description_es')
                            ->label('Description (ES)'),
                    ])->columns(2),

                Forms\Components\Section::make('Features')
                    ->schema([
                        Forms\Components\Repeater::make('features')
                            ->schema([
                                Forms\Components\TextInput::make('feature_en')->required()->label('Feature (EN)'),
                                Forms\Components\TextInput::make('feature_es')->required()->label('Feature (ES)'),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                    ]),

                Forms\Components\Section::make('Status & Ordering')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured Plan')
                            ->helperText('Highlight this plan on the frontend.')
                            ->default(false),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                    ])->columns(3),
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
                        Tables\Columns\TextColumn::make('id')
                            ->badge()
                            ->color(fn (Plan $record): string => auth()->user()?->plan_id === $record->id ? 'success' : 'gray')
                            ->formatStateUsing(fn (Plan $record): string => auth()->user()?->plan_id === $record->id ? 'Your Current Plan ✓' : match($record->billing_cycle) {
                                'every_4_weeks' => 'Every 4 Weeks',
                                'one-time' => 'One-Time',
                                'monthly' => 'Monthly',
                                'annual' => 'Annual',
                                default => ucfirst($record->billing_cycle),
                            }),
                        Tables\Columns\TextColumn::make('name_en')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
                        Tables\Columns\TextColumn::make('price')
                            ->money('usd')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->color('primary')
                            ->suffix(fn (Plan $record): string => ' USD / ' . match($record->billing_cycle) {
                                'every_4_weeks' => '4 weeks',
                                default => $record->billing_cycle,
                            }),
                        Tables\Columns\TextColumn::make('description_en')
                            ->color('gray')
                            ->limit(140),
                    ])->space(2),
                ])
                ->defaultSort('sort_order', 'asc')
                ->actions([
                    Tables\Actions\Action::make('upgrade')
                        ->label(fn (Plan $record): string => auth()->user()?->plan_id === $record->id ? 'Active Plan' : 'Switch to This Plan')
                        ->icon(fn (Plan $record): string => auth()->user()?->plan_id === $record->id ? 'heroicon-m-check-badge' : 'heroicon-m-arrow-up-circle')
                        ->button()
                        ->color(fn (Plan $record): string => auth()->user()?->plan_id === $record->id ? 'success' : 'primary')
                        ->disabled(fn (Plan $record): bool => auth()->user()?->plan_id === $record->id)
                        ->url(fn (Plan $record): ?string => $record->stripe_payment_link, shouldOpenInNewTab: true)
                        ->visible(fn (Plan $record): bool => auth()->user()?->plan_id === $record->id || filled($record->stripe_payment_link)),
                ])
                ->bulkActions([]);
        }

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_en')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('price')->money('usd')->sortable(),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'every_4_weeks' => 'Every 4 Weeks (28 days)',
                        'one-time' => 'One-Time',
                        'monthly' => 'Monthly',
                        'annual' => 'Annual',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match($state) {
                        'every_4_weeks' => 'info',
                        'monthly' => 'primary',
                        'annual' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_featured')->boolean()->label('Featured'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order', 'asc')
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
            ])
            ->reorderable('sort_order');
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
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
