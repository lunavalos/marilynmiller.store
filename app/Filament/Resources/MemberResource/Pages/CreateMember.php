<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use App\Notifications\WelcomeUserNotification;
use Filament\Resources\Pages\CreateRecord;

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

        $this->record->notify(new WelcomeUserNotification(
            plainPassword: $this->data['password'] ?? null,
            panel: 'member',
            language: $this->data['preferred_language'] ?? $this->record->preferred_language ?? 'en',
        ));
    }
}
