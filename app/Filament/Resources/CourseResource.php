<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Filament\Resources\CourseResource\RelationManagers;
use App\Models\Course;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CourseResource extends Resource
{
    use \App\Filament\Concerns\HasResourcePermissions;

    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'E-Learning';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withCount('modules');

        if (static::isSubscriber()) {
            $planId = auth()->user()?->plan_id;
            $query->where('is_published', true)
                ->where(function (Builder $q) use ($planId) {
                    $q->whereDoesntHave('plans');
                    if ($planId) {
                        $q->orWhereHas('plans', fn (Builder $p) => $p->where('plans.id', $planId));
                    }
                });
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Course Details')
                    ->schema([
                        Forms\Components\TextInput::make('title_en')
                            ->required()
                            ->maxLength(255)
                            ->label('Title (EN)'),
                        Forms\Components\TextInput::make('title_es')
                            ->required()
                            ->maxLength(255)
                            ->label('Title (ES)'),
                        Forms\Components\Textarea::make('description_en')
                            ->label('Description (EN)')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description_es')
                            ->label('Description (ES)')
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('thumbnail_url')
                            ->image()
                            ->directory('courses')
                            ->label('Course Thumbnail')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('plans')
                            ->relationship('plans', 'name_en')
                            ->multiple()
                            ->preload()
                            ->label('Available in Plans (Leave empty to make it available to all)'),
                        Forms\Components\TextInput::make('stripe_payment_link')
                            ->url()
                            ->maxLength(255)
                            ->label('Stripe Payment Link (Optional for paid courses)'),
                        Forms\Components\Toggle::make('is_published')
                            ->label('Published')
                            ->default(false),
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
                        Tables\Columns\ImageColumn::make('thumbnail_url')
                            ->height('180px')
                            ->extraImgAttributes(['class' => 'w-full object-cover rounded-lg mb-3']),
                        Tables\Columns\TextColumn::make('modules_count')
                            ->badge()
                            ->color('primary')
                            ->formatStateUsing(fn ($state) => "{$state} " . \Illuminate\Support\Str::plural('Module', (int) $state)),
                        Tables\Columns\TextColumn::make('title_en')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                            ->searchable(),
                        Tables\Columns\TextColumn::make('description_en')
                            ->color('gray')
                            ->limit(110),
                    ])->space(2),
                ])
                ->recordUrl(fn (Course $record): string => static::getUrl('view', ['record' => $record]))
                ->actions([
                    Tables\Actions\ViewAction::make()
                        ->label('Watch Course')
                        ->icon('heroicon-m-play-circle')
                        ->button()
                        ->color('primary'),
                ])
                ->bulkActions([]);
        }

        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_url')
                    ->label('Thumbnail'),
                Tables\Columns\TextColumn::make('title_en')
                    ->label('Title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('modules_count')
                    ->label('Modules')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()
                    ->label('Published'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Preview'),
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
            RelationManagers\ModulesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'view' => Pages\ViewCourse::route('/{record}'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}
