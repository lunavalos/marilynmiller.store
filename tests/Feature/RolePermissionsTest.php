<?php

namespace Tests\Feature;

use App\Filament\Resources\RoleResource\Pages\ManageRoles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic permissions
        Permission::findOrCreate('view_courses');
        Permission::findOrCreate('create_courses');
        Permission::findOrCreate('update_courses');
        Permission::findOrCreate('view_videos');
        Permission::findOrCreate('create_videos');
        Permission::findOrCreate('view_drop_in_bookings');
    }

    public function test_role_permissions_hydrate_as_indexed_lists(): void
    {
        $role = Role::findOrCreate('subscriber_test');
        $role->syncPermissions(['view_courses', 'view_videos']);

        // Check the role's permissions
        $permissions = $role->permissions()->pluck('name')->toArray();
        $this->assertContains('view_courses', $permissions);
        $this->assertContains('view_videos', $permissions);

        // Filter for courses slug and verify it is a zero-indexed array (list)
        $coursePerms = array_values(
            array_filter($permissions, fn ($name) => str_ends_with($name, '_courses'))
        );

        $this->assertTrue(array_is_list($coursePerms));
        $this->assertEquals('["view_courses"]', json_encode($coursePerms));

        // Filter for videos slug and verify it is also a zero-indexed array (list)
        $videoPerms = array_values(
            array_filter($permissions, fn ($name) => str_ends_with($name, '_videos'))
        );

        $this->assertTrue(array_is_list($videoPerms));
        $this->assertEquals('["view_videos"]', json_encode($videoPerms));
    }

    public function test_can_edit_role_permissions_via_manage_roles_page(): void
    {
        $admin = User::factory()->create();
        $adminRole = Role::findOrCreate('super_admin');
        $admin->assignRole($adminRole);

        $role = Role::findOrCreate('editor_test');
        $role->syncPermissions(['view_courses']);

        Livewire::actingAs($admin)
            ->test(ManageRoles::class)
            ->mountTableAction('edit', $role)
            ->assertTableActionDataSet([
                'name' => 'editor_test',
                'permissions_courses' => ['view_courses'],
                'permissions_videos' => [],
            ])
            ->setTableActionData([
                'name' => 'editor_test',
                'permissions_courses' => ['view_courses', 'create_courses'],
                'permissions_videos' => ['view_videos'],
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('view_courses'));
        $this->assertTrue($role->hasPermissionTo('create_courses'));
        $this->assertTrue($role->hasPermissionTo('view_videos'));
        $this->assertFalse($role->hasPermissionTo('update_courses'));
    }
}
