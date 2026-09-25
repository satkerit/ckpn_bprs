<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_role_permission_cannot_open_role_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/roles')
            ->assertForbidden();
    }

    public function test_user_with_role_permission_can_open_role_page(): void
    {
        $permission = Permission::create(['name' => 'role.manage', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Administrator', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get('/admin/roles')
            ->assertOk();
    }
}
