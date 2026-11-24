<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LiveEventResource\Pages;
use App\Filament\Resources\LiveEventResource\RelationManagers;
use App\Models\LiveEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LiveEventResource extends Resource
{
    use \App\Filament\Concerns\HasResourcePermissions;

    protected static ?string $model = LiveEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationGroup = 'E-Learning';

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
                Forms\Components\Section::make('Event Details')
                    ->schema([
                        Forms\Components\TextInput::make('title_en')->required()->maxLength(255)->label('Title (EN)'),
                        Forms\Components\TextInput::make('title_es')->required()->maxLength(255)->label('Title (ES)'),
                        Forms\Components\DateTimePicker::make('start_time')->required()->label('Start Time'),
                        Forms\Components\DateTimePicker::make('end_time')->label('End Time'),
                        Forms\Components\TextInput::make('meeting_url')->required()->url()->maxLength(255)->label('Meeting URL (Zoom/Meet)')->columnSpanFull(),
                        Forms\Components\Textarea::make('description_en')->label('Description (EN)'),
                        Forms\Components\Textarea::make('description_es')->label('Description (ES)'),
                        Forms\Components\FileUpload::make('thumbnail_url')->image()->directory('live_events')->label('Thumbnail')->columnSpanFull(),
                        Forms\Components\Toggle::make('is_published')->label('Published')->default(false),
                        Forms\Components\Select::make('plans')
                            ->relationship('plans', 'name_en')
                            ->multiple()
                            ->preload()
                            ->label('Available in Plans (Leave empty for all)'),
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
                        Tables\Columns\TextColumn::make('start_time')
                            ->dateTime('M d, Y · g:i A')
                            ->badge()
                            ->color('success')
                            ->icon('heroicon-m-calendar'),
                        Tables\Columns\TextColumn::make('title_en')
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                            ->searchable(),
                        Tables\Columns\TextColumn::make('description_en')
                            ->color('gray')
                            ->limit(110),
                    ])->space(2),
                ])
                ->defaultSort('start_time', 'asc')
                ->actions([
                    Tables\Actions\Action::make('join')
                        ->label('Join Live Event')
                        ->icon('heroicon-m-video-camera')
                        ->button()
                        ->color('primary')
                        ->url(fn (LiveEvent $record): ?string => $record->meeting_url, shouldOpenInNewTab: true)
                        ->visible(fn (LiveEvent $record): bool => filled($record->meeting_url)),
                ])
                ->bulkActions([]);
        }

        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_url')->label('Thumbnail'),
                Tables\Columns\TextColumn::make('title_en')->searchable()->label('Title'),
                Tables\Columns\TextColumn::make('start_time')->dateTime()->sortable()->label('Date & Time'),
                Tables\Columns\IconColumn::make('is_published')->boolean()->label('Published'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListLiveEvents::route('/'),
            'create' => Pages\CreateLiveEvent::route('/create'),
            'edit' => Pages\EditLiveEvent::route('/{record}/edit'),
        ];
    }
}
