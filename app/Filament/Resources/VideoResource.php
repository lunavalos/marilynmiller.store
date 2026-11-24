<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoResource\Pages;
use App\Filament\Resources\VideoResource\RelationManagers;
use App\Models\Video;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VideoResource extends Resource
{
    use \App\Filament\Concerns\HasResourcePermissions;

    protected static ?string $model = Video::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationGroup = 'E-Learning';

    public static function getNavigationLabel(): string
    {
        return static::isSubscriber() ? 'On-Demand Videos' : 'Videos';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

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
                Forms\Components\Section::make('Video Details')
                    ->schema([
                        Forms\Components\TextInput::make('title_en')->required()->maxLength(255)->label('Title (EN)'),
                        Forms\Components\TextInput::make('title_es')->required()->maxLength(255)->label('Title (ES)'),
                        Forms\Components\TextInput::make('video_url')->required()->url()->maxLength(255)->label('Video URL')->columnSpanFull(),
                        Forms\Components\Textarea::make('description_en')->label('Description (EN)'),
                        Forms\Components\Textarea::make('description_es')->label('Description (ES)'),
                        Forms\Components\FileUpload::make('thumbnail_url')->image()->directory('videos')->label('Thumbnail')->columnSpanFull(),
                        Forms\Components\TextInput::make('duration_minutes')->numeric()->label('Duration (minutes)'),
                        Forms\Components\Toggle::make('is_published')->label('Published')->default(false),
                        Forms\Components\Select::make('plans')
                            ->relationship('plans', 'name_en')
                            ->multiple()
                            ->preload()
                            ->label('Available in Plans (Leave empty for all)')
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
                        Tables\Columns\ImageColumn::make('thumbnail_url')
                            ->height('180px')
                            ->extraImgAttributes(['class' => 'w-full object-cover rounded-lg mb-3']),
                        Tables\Columns\TextColumn::make('duration_minutes')
                            ->badge()
                            ->color('info')
                            ->formatStateUsing(fn ($state) => $state ? "{$state} min" : 'On-Demand'),
                        Tables\Columns\TextColumn::make('title_en')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                            ->searchable(),
                        Tables\Columns\TextColumn::make('description_en')
                            ->color('gray')
                            ->limit(100),
                    ])->space(2),
                ])
                ->recordUrl(fn (Video $record): string => static::getUrl('view', ['record' => $record]))
                ->actions([
                    Tables\Actions\ViewAction::make()
                        ->label('Watch Video')
                        ->icon('heroicon-m-play')
                        ->button()
                        ->color('primary'),
                ])
                ->bulkActions([]);
        }

        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_url')->label('Thumbnail'),
                Tables\Columns\TextColumn::make('title_en')->searchable()->label('Title'),
                Tables\Columns\TextColumn::make('duration_minutes')->label('Duration (mins)'),
                Tables\Columns\IconColumn::make('is_published')->boolean()->label('Published'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVideos::route('/'),
            'create' => Pages\CreateVideo::route('/create'),
            'view' => Pages\ViewVideo::route('/{record}'),
            'edit' => Pages\EditVideo::route('/{record}/edit'),
        ];
    }
}
