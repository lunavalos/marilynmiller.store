<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Define roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'member']);

        // Create admin user
        $user = User::factory()->create([
            'name' => 'Marilyn Miller',
            'email' => 'admin@millyfit.test',
            'password' => bcrypt('password'),
        ]);

        $user->assignRole($superAdmin);
    }
}
