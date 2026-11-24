<?php

namespace App\Filament\Pages;

use App\Models\WebsiteContent as ContentModel;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class HeroContent extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationGroup = 'Website Content';
    protected static ?string $title = 'Hero Section';
    protected static ?string $navigationLabel = 'Hero Section';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.content-page';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public function mount(): void
    {
        $content = ContentModel::first();
        if ($content && $content->hero) {
            $this->form->fill($content->hero);
        } else {
            $this->form->fill();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('eyebrow')
                        ->label('Eyebrow Text (EN)')
                        ->maxLength(255),
                    TextInput::make('eyebrow_es')
                        ->label('Eyebrow Text (ES)')
                        ->maxLength(255),
                    TextInput::make('title')
                        ->label('Main Title (EN)')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('title_es')
                        ->label('Main Title (ES)')
                        ->maxLength(255),
                    Textarea::make('subtitle')
                        ->label('Subtitle (EN)')
                        ->rows(3),
                    Textarea::make('subtitle_es')
                        ->label('Subtitle (ES)')
                        ->rows(3),
                    TextInput::make('pill_text')
                        ->label('Pill Text Under Image (EN)')
                        ->maxLength(255),
                    TextInput::make('pill_text_es')
                        ->label('Pill Text Under Image (ES)')
                        ->maxLength(255),
                    FileUpload::make('image')
                        ->label('Right Image')
                        ->image()
                        ->directory('hero')
                        ->columnSpanFull(),
                ])
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $content = ContentModel::first() ?? new ContentModel();
        
        $content->hero = $this->form->getState();
        $content->save();

        Notification::make()
            ->success()
            ->title('Hero content saved successfully')
            ->send();
    }
}
