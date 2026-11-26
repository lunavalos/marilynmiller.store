<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Notifications\WelcomeUserNotification;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Log;
use Throwable;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->after(function (User $record, array $data): void {
                    try {
                        $record->notify(new WelcomeUserNotification(
                            plainPassword: $data['password'] ?? null,
                            panel: 'admin',
                            language: $data['preferred_language'] ?? $record->preferred_language ?? 'en',
                        ));
                    } catch (Throwable $e) {
                        Log::error('Failed to send welcome email to system admin', [
                            'user_id' => $record->id,
                            'email' => $record->email,
                            'error' => $e->getMessage(),
                        ]);

                        Notification::make()
                            ->warning()
                            ->title('User created, but welcome email could not be sent')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
