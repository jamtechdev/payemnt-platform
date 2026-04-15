<?php

namespace Tests\Feature\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_super_admin_redirects_to_platform_dashboard(): void
    {
        Role::query()->firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['password' => bcrypt('ChangeMe12345!')]);
        $user->assignRole('super_admin');

        $response = $this->post('/login', ['email' => $user->email, 'password' => 'ChangeMe12345!']);

        $response->assertRedirect(route('admin.platform.dashboard'));
    }
}
