<?php

namespace App\Filament\Pages;

use App\Models\WebsiteContent as ContentModel;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class StatsContent extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Website Content';
    protected static ?string $title = 'Company Stats';
    protected static ?string $navigationLabel = 'Stats';

    protected static string $view = 'filament.pages.content-page';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public function mount(): void
    {
        $content = ContentModel::first();
        if ($content) {
            $this->form->fill($content->toArray());
        } else {
            $this->form->fill();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('stats')
                    ->schema([
                        TextInput::make('value')
                            ->required()
                            ->label('Value / Number (e.g. "500", "8+", "100%")'),
                        TextInput::make('label_en')
                            ->required()
                            ->label('Label (EN, e.g. "WOMEN TRANSFORMED")'),
                        TextInput::make('label_es')
                            ->required()
                            ->label('Label (ES, e.g. "MUJERES TRANSFORMADAS")'),
                    ])
                    ->collapsible()
                    ->defaultItems(0)
                    ->maxItems(4) // Usually 4 stats fit nicely
                    ->addActionLabel('Add Stat'),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $content = ContentModel::first();
        if ($content) {
            $content->update($this->form->getState());
            Notification::make()
                ->success()
                ->title('Stats saved successfully')
                ->send();
        }
    }
}
