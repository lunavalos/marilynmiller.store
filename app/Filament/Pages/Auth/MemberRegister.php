<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MemberRegister extends BaseRegister
{
    protected function handleRegistration(array $data): Model
    {
        $data['is_active'] = true;

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

        return $user;
    }
}
