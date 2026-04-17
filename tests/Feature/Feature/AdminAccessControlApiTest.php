<?php

namespace Tests\Feature\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessControlApiTest extends TestCase
{
    use RefreshDatabase;

    private function seedUserManagementPermissions(): void
    {
        foreach (['users.assign_roles', 'users.edit', 'users.deactivate'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
    }

    public function test_admin_cannot_assign_super_admin_role(): void
    {
        $this->seedUserManagementPermissions();
        $adminRole = Role::findOrCreate('admin', 'web');
        $superRole = Role::findOrCreate('super_admin', 'web');
        $adminRole->givePermissionTo('users.assign_roles');

        $actor = User::factory()->create();
        $actor->assignRole($adminRole);

        $target = User::factory()->create();
        $target->assignRole($adminRole);

        Sanctum::actingAs($actor);

        $this->patchJson("/api/v1/users/{$target->id}/access-control", [
            'role' => $superRole->name,
        ])->assertStatus(403);
    }

    public function test_super_admin_cannot_update_own_profile_or_access_control(): void
    {
        $this->seedUserManagementPermissions();
        $superRole = Role::findOrCreate('super_admin', 'web');
        $superRole->givePermissionTo(['users.assign_roles', 'users.edit']);

        $actor = User::factory()->create();
        $actor->assignRole($superRole);
        Sanctum::actingAs($actor);

        $this->patchJson("/api/v1/users/{$actor->id}", [
            'name' => 'New Name',
        ])->assertStatus(403);

        $this->patchJson("/api/v1/users/{$actor->id}/access-control", [
            'role' => 'admin',
        ])->assertStatus(403);
    }

    public function test_admin_cannot_update_or_delete_super_admin(): void
    {
        $this->seedUserManagementPermissions();
        $adminRole = Role::findOrCreate('admin', 'web');
        $superRole = Role::findOrCreate('super_admin', 'web');
        $adminRole->givePermissionTo(['users.edit', 'users.deactivate']);

        $actor = User::factory()->create();
        $actor->assignRole($adminRole);

        $superTarget = User::factory()->create();
        $superTarget->assignRole($superRole);

        Sanctum::actingAs($actor);

        $this->patchJson("/api/v1/users/{$superTarget->id}", [
            'name' => 'Blocked Update',
        ])->assertStatus(403);

        $this->deleteJson("/api/v1/users/{$superTarget->id}")
            ->assertStatus(403);
    }
}
