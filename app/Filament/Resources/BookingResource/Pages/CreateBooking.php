<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Models\Session;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function beforeCreate(): void
    {
        $session = Session::find($this->data['session_id'] ?? null);
        if ($session && $session->is_full) {
            Notification::make()
                ->danger()
                ->title('Session is Full')
                ->body("Cannot create booking. This session has reached its capacity limit of {$session->max_seats} seats.")
                ->send();

            $this->halt();
        }
    }
}
