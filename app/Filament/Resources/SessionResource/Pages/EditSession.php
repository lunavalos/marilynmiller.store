<?php

namespace App\Filament\Resources\SessionResource\Pages;

use App\Filament\Resources\SessionResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSession extends EditRecord
{
    protected static string $resource = SessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('syncRecurrence')
                ->label('Regenerate Occurrences')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->visible(fn (): bool => (bool) $this->record->is_recurring)
                ->action(function () {
                    $count = $this->record->syncRecurringInstances();
                    Notification::make()
                        ->success()
                        ->title('Occurrences Updated')
                        ->body("Synced {$count} recurring occurrences for this series.")
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        if ($this->record->is_recurring) {
            $this->record->syncRecurringInstances();
        }
    }
}
