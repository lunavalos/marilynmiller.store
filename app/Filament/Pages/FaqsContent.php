<?php

namespace App\Filament\Pages;

use App\Models\WebsiteContent as ContentModel;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class FaqsContent extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Website Content';

    protected static ?string $title = 'FAQs';

    protected static ?string $navigationLabel = 'FAQs';

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
                Repeater::make('faqs')
                    ->schema([
                        TextInput::make('question_en')->required()->label('Question (EN)'),
                        TextInput::make('question_es')->required()->label('Question (ES)'),
                        Textarea::make('answer_en')->required()->label('Answer (EN)'),
                        Textarea::make('answer_es')->required()->label('Answer (ES)'),
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
            ->title('FAQs saved successfully')
            ->send();
    }
}
