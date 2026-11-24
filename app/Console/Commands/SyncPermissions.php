<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Filament\Facades\Filament;

#[Signature('app:sync-permissions')]
#[Description('Generate permissions for all Filament resources')]
class SyncPermissions extends Command
{
    public function handle()
    {
        $resources = Filament::getPanel('admin')->getResources();
        $actions = ['view_any', 'view', 'create', 'update', 'delete', 'delete_any'];

        foreach ($resources as $resource) {
            $name = str($resource::getModelLabel())->plural()->slug('_')->toString();

            foreach ($actions as $action) {
                $permissionName = "{$action}_{$name}";
                Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'web']);
            }
        }

        $this->info('Permissions generated successfully!');
    }
}
