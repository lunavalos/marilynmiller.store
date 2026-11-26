<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use App\Notifications\WelcomeUserNotification;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Throwable;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $this->record->assignRole('subscribed');

        try {
            $this->record->notify(new WelcomeUserNotification(
                plainPassword: $this->data['password'] ?? null,
                panel: 'member',
                language: $this->data['preferred_language'] ?? $this->record->preferred_language ?? 'en',
            ));
        } catch (Throwable $e) {
            Log::error('Failed to send welcome email to member', [
                'user_id' => $this->record->id,
                'email' => $this->record->email,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->warning()
                ->title('Member created, but welcome email could not be sent')
                ->body($e->getMessage())
                ->send();
        }
    }
}
