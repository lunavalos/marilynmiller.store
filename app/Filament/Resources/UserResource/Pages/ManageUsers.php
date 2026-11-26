<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Notifications\WelcomeUserNotification;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->after(function (User $record, array $data): void {
                    $record->notify(new WelcomeUserNotification(
                        plainPassword: $data['password'] ?? null,
                        panel: 'admin',
                        language: $data['preferred_language'] ?? $record->preferred_language ?? 'en',
                    ));
                }),
        ];
    }
}
