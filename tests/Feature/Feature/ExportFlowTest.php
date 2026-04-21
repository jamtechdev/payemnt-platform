<?php

namespace Tests\Feature\Feature;

use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExportFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_export_job_can_be_polled_and_downloaded(): void
    {
        foreach (['customers.view_list', 'customers.export'] as $perm) {
            Permission::query()->firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $role = Role::query()->firstOrCreate(['name' => 'customer_service', 'guard_name' => 'web']);
        $role->syncPermissions(['customers.view_list', 'customers.export']);

        $user = User::factory()->create();
        $user->assignRole('customer_service');
        $this->actingAs($user);

        $partner = Partner::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'partner_code' => 'PARTNER_X',
            'name' => 'Partner X',
            'slug' => 'partner-x',
            'contact_email' => 'partnerx@test.local',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'product_code' => 'PROD_X',
            'name' => 'Product X',
            'slug' => 'product-x',
            'status' => 'active',
            'cover_duration_options' => [12],
        ]);
        Customer::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@test.local',
            'start_date' => now()->toDateString(),
            'cover_duration_days' => 30,
            'customer_since' => now()->toDateString(),
            'status' => 'active',
        ]);

        $job = $this->postJson(route('admin.customers.export'))->assertOk()->json('job_id');
        $this->assertNotEmpty($job);

        $this->get(route('admin.customers.download-export', $job))->assertOk();
    }
}
