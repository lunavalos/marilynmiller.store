<?php

namespace App\Filament\Pages;

use App\Models\WebsiteContent as ContentModel;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class GalleryContent extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Website Content';
    protected static ?string $title = 'Gallery';
    protected static ?string $navigationLabel = 'Gallery';

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
                FileUpload::make('gallery')
                    ->multiple()
                    ->image()
                    ->directory('gallery')
                    ->label('Photo Gallery (Everything you need)'),
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
                ->title('Gallery saved successfully')
                ->send();
        }
    }
}
