<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $subscribed = Role::firstOrCreate(['name' => 'subscribed', 'guard_name' => 'web']);

        $subscriberPermissions = [
            'view_courses',
            'view_videos',
            'view_live_events',
            'view_agenda_sessions',
            'view_drop_in_bookings',
            'view_plans',
        ];

        foreach ($subscriberPermissions as $perm) {
            $permission = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $subscribed->givePermissionTo($permission);
        }

        // Create initial super admin user if it doesn't exist
        $user = User::firstOrCreate(
            ['email' => 'admin@millyfit.pro'],
            [
                'name' => 'Marilyn Miller',
                'password' => Hash::make('Millyfit2026!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        if (! $user->hasRole('super_admin')) {
            $user->assignRole($superAdmin);
        }
    }
}
