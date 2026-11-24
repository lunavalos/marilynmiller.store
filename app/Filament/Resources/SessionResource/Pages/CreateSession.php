<?php

namespace App\Filament\Resources\SessionResource\Pages;

use App\Filament\Resources\SessionResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSession extends CreateRecord
{
    protected static string $resource = SessionResource::class;

    protected function afterCreate(): void
    {
        if ($this->record->is_recurring) {
            $count = $this->record->syncRecurringInstances();
            if ($count > 0) {
                Notification::make()
                    ->success()
                    ->title('Recurring Sessions Scheduled')
                    ->body("Generated {$count} upcoming session occurrences in the calendar.")
                    ->send();
            }
        }
    }
}
