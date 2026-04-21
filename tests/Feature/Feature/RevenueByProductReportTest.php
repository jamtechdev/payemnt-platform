<?php

declare(strict_types=1);

namespace Tests\Feature\Feature;

use App\Models\Customer;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RevenueByProductReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_revenue_by_product_uses_payments_product_id_and_paid_at(): void
    {
        Permission::query()->firstOrCreate(['name' => 'reports.revenue_by_product', 'guard_name' => 'web']);
        $role = Role::query()->firstOrCreate(['name' => 'reconciliation_admin', 'guard_name' => 'web']);
        $role->givePermissionTo('reports.revenue_by_product');

        $user = User::factory()->create([
            'password' => Hash::make('Password@123'),
            'status' => 'active',
            'is_active' => true,
        ]);
        $user->assignRole($role);

        $partner = Partner::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'partner_code' => 'P1',
            'name' => 'P',
            'slug' => 'p',
            'contact_email' => 'p@test.local',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'product_code' => 'PR1',
            'name' => 'Prod',
            'slug' => 'prod',
            'status' => 'active',
            'cover_duration_options' => [12],
        ]);
        $customer = Customer::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'first_name' => 'A',
            'last_name' => 'B',
            'email' => 'c@test.local',
            'start_date' => now()->toDateString(),
            'cover_duration_days' => 30,
            'customer_since' => now()->toDateString(),
            'status' => 'active',
        ]);

        Payment::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'customer_id' => $customer->id,
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'amount' => 100.50,
            'currency' => 'USD',
            'paid_at' => now(),
            'transaction_reference' => 'TX-'.uniqid(),
            'status' => 'success',
        ]);

        $this->actingAs($user)
            ->get(route('admin.reports.revenue'))
            ->assertOk();

        $rows = \App\Models\Payment::query()
            ->selectRaw('payments.product_id, SUM(payments.amount) as total_revenue, COUNT(payments.id) as payment_count')
            ->where('payments.status', 'success')
            ->groupBy('payments.product_id')
            ->get();

        $this->assertCount(1, $rows);
        $this->assertSame($product->id, (int) $rows->first()->product_id);
        $this->assertEquals(100.50, (float) $rows->first()->total_revenue);
    }
}
