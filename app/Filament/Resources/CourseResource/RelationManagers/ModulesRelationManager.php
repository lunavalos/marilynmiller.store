<?php

namespace App\Filament\Resources\CourseResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ModulesRelationManager extends RelationManager
{
    protected static string $relationship = 'modules';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title_en')
                    ->required()
                    ->maxLength(255)
                    ->label('Module Title (EN)'),
                Forms\Components\TextInput::make('title_es')
                    ->required()
                    ->maxLength(255)
                    ->label('Module Title (ES)'),
                Forms\Components\TextInput::make('sort_order')
                    ->numeric()
                    ->default(0)
                    ->label('Order'),

                Forms\Components\Section::make('Lessons (Videos)')
                    ->schema([
                        Forms\Components\Repeater::make('lessons')
                            ->relationship('lessons') // Ties to the lessons() method on CourseModule
                            ->schema([
                                Forms\Components\TextInput::make('title_en')->required()->label('Title (EN)'),
                                Forms\Components\TextInput::make('title_es')->required()->label('Title (ES)'),
                                Forms\Components\TextInput::make('video_url')->url()->label('Video URL (YouTube/Vimeo)'),
                                Forms\Components\TextInput::make('duration_minutes')->numeric()->label('Duration (mins)'),
                                Forms\Components\Textarea::make('description_en')->label('Description (EN)'),
                                Forms\Components\Textarea::make('description_es')->label('Description (ES)'),
                                Forms\Components\TextInput::make('sort_order')->numeric()->default(0)->label('Order'),
                            ])
                            ->orderColumn('sort_order')
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title_en'] ?? null)
                            ->columns(2),
                    ])
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title_en')
            ->columns([
                Tables\Columns\TextColumn::make('title_en')->label('Module Title'),
                Tables\Columns\TextColumn::make('lessons_count')
                    ->counts('lessons')
                    ->label('Lessons'),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
