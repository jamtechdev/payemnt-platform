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

    public function test_admin_cannot_assign_super_admin_role(): void
    {
        Permission::findOrCreate('users.assign_roles', 'web');
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
}
