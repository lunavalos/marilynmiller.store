<?php

namespace App\Filament\Pages\Auth;

use App\Notifications\WelcomeUserNotification;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

class MemberRegister extends BaseRegister
{
    protected ?string $rawPassword = null;

    /**
     * @return array<int | string, string | Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPreferredLanguageFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    protected function getPreferredLanguageFormComponent(): Component
    {
        return Select::make('preferred_language')
            ->label('Preferred Language / Idioma preferido')
            ->options([
                'en' => 'English',
                'es' => 'Español',
            ])
            ->default('en')
            ->required();
    }

    protected function beforeValidate(): void
    {
        $this->rawPassword = is_string($this->data['password'] ?? null) ? $this->data['password'] : null;
    }

    protected function handleRegistration(array $data): Model
    {
        $data['is_active'] = true;
        $data['preferred_language'] = $data['preferred_language'] ?? 'en';

        $user = $this->getUserModel()::create($data);

        $role = Role::firstOrCreate([
            'name' => 'subscribed',
            'guard_name' => 'web',
        ]);

        // Ensure subscriber view permissions exist on fresh databases
        $subscriberPermissions = [
            'view_courses',
            'view_videos',
            'view_live_events',
            'view_agenda_sessions',
            'view_drop_in_bookings',
            'view_plans',
        ];

        foreach ($subscriberPermissions as $perm) {
            $permission = Permission::firstOrCreate([
                'name' => $perm,
                'guard_name' => 'web',
            ]);
            if (! $role->hasPermissionTo($permission)) {
                $role->givePermissionTo($permission);
            }
        }

        $user->assignRole($role);

        try {
            $user->notify(new WelcomeUserNotification(
                plainPassword: $this->rawPassword,
                panel: 'member',
                language: $user->preferred_language,
            ));
        } catch (Throwable $e) {
            Log::error('Failed to send welcome email on member self-registration', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        return $user;
    }
}
