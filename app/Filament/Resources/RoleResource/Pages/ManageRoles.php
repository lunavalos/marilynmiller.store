<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->using(function (array $data, string $model) {
                    $permissions = [];
                    foreach ($data as $key => $value) {
                        if (str_starts_with($key, 'permissions_') && is_array($value)) {
                            $permissions = array_merge($permissions, array_values($value));
                        }
                    }
                    $role = $model::create(['name' => $data['name']]);
                    $role->syncPermissions(array_values(array_unique(array_filter($permissions))));
                    return $role;
                }),
        ];
    }
}
