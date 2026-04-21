<?php

declare(strict_types=1);

namespace Tests\Feature\Feature;

use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_reconciliation_admin_cannot_access_customer_details_route_without_permission(): void
    {
        $role = Role::query()->create(['name' => 'reconciliation_admin', 'guard_name' => 'web']);
        $user = User::query()->create([
            'name' => 'Recon Admin',
            'email' => 'recon@test.local',
            'password' => Hash::make('Password@123'),
            'status' => 'active',
            'is_active' => true,
        ]);
        $user->assignRole($role);

        $partner = Partner::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_code' => 'RBAC_P',
            'name' => 'P',
            'slug' => 'rbac-p',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'uuid' => (string) Str::uuid(),
            'product_code' => 'RBAC_PR',
            'name' => 'Pr',
            'slug' => 'rbac-pr',
            'status' => 'active',
            'cover_duration_options' => [12],
        ]);
        $customer = Customer::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'first_name' => 'A',
            'last_name' => 'B',
            'start_date' => now()->toDateString(),
            'cover_duration_days' => 30,
            'customer_since' => now()->toDateString(),
            'status' => 'active',
        ]);

        $token = $user->createToken('admin')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/admin/customers/'.$customer->id)
            ->assertStatus(403);
    }
}
