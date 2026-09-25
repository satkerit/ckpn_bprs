<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_cannot_open_permission_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/permissions')
            ->assertForbidden();
    }

    public function test_user_with_permission_can_open_permission_page(): void
    {
        $permission = Permission::create(['name' => 'permission.manage', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'Administrator', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get('/admin/permissions')
            ->assertOk();
    }
}
