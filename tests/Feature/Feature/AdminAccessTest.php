<?php

namespace Tests\Feature\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_swagger_ui_route_is_public(): void
    {
        $this->get('/api/documentation')->assertStatus(200);
    }

    public function test_customer_service_cannot_access_super_admin_dashboard(): void
    {
        Permission::query()->firstOrCreate(['name' => 'dashboard.customer_overview', 'guard_name' => 'web']);
        Permission::query()->firstOrCreate(['name' => 'customers.view_list', 'guard_name' => 'web']);
        $csRole = Role::query()->firstOrCreate(['name' => 'customer_service', 'guard_name' => 'web']);
        $csRole->syncPermissions(['dashboard.customer_overview', 'customers.view_list']);

        $user = User::factory()->create();
        $user->assignRole('customer_service');
        $this->actingAs($user);

        $this->get(route('admin.platform.dashboard'))->assertStatus(403);
    }
}
