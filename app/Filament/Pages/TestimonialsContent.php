<?php

namespace App\Filament\Pages;

use App\Models\WebsiteContent as ContentModel;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TestimonialsContent extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?string $navigationGroup = 'Website Content';

    protected static ?string $title = 'Testimonials';

    protected static ?string $navigationLabel = 'Testimonials';

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
                Repeater::make('testimonials')
                    ->schema([
                        TextInput::make('author')->required()->label('Author Name'),
                        TextInput::make('time_en')->label('Time (EN, e.g. "6 months")'),
                        TextInput::make('time_es')->label('Time (ES, e.g. "6 meses")'),
                        Textarea::make('quote_en')->required()->label('Quote (EN)'),
                        Textarea::make('quote_es')->required()->label('Quote (ES)'),
                        FileUpload::make('image')
                            ->image()
                            ->directory('testimonials')
                            ->label('Author Image'),
                    ])
                    ->collapsible()
                    ->defaultItems(0),
            ])
            ->statePath('data');
    }

    public function submit(): void
    {
        $content = ContentModel::first() ?? new ContentModel;
        $content->fill($this->form->getState());
        $content->save();

        Notification::make()
            ->success()
            ->title('Testimonials saved successfully')
            ->send();
    }
}
