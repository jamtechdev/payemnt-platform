<?php

namespace Tests\Feature\Feature;

use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductField;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartnerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_bearer_token_returns_401(): void
    {
        $response = $this->postJson('/api/v1/partner/customers', []);
        $response->assertStatus(401);
    }

    public function test_valid_customer_submission_returns_201(): void
    {
        $partner = Partner::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Partner One',
            'slug' => 'partner-one',
            'email' => 'partner1@test.local',
            'status' => 'active',
            'is_active' => true,
            'password' => bcrypt('Secret12345!'),
        ]);
        Role::query()->firstOrCreate(['name' => 'partner', 'guard_name' => 'web']);
        $partner->syncRoles(['partner']);
        $product = Product::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Basic',
            'slug' => 'basic',
            'status' => 'active',
            'cover_duration_options' => [12],
        ]);
        \DB::table('partner_products')->insert([
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'status' => 'active',
            'partner_price' => 75.50,
            'partner_currency' => 'USD',
            'activated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        ProductField::query()->create([
            'product_id' => $product->id,
            'name' => 'first_name',
            'label' => 'First Name',
            'type' => 'text',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $payload = [
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'customer_data' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'cover_start_date' => now()->toDateString(),
                'cover_duration_months' => 12,
            ],
            'payment' => [
                'amount' => 120.50,
                'currency' => 'USD',
                'payment_date' => now()->toIso8601String(),
                'transaction_reference' => 'TX-001',
            ],
        ];

        $tokenResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $partner->email,
            'password' => 'Secret12345!',
        ])->assertOk();
        $token = $tokenResponse->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/partner/customers', $payload)
            ->assertStatus(201)
            ->assertJsonStructure(['status', 'data' => ['customer_id', 'customer_uuid', 'message']]);
    }

    public function test_rate_limit_returns_429_with_retry_after_header(): void
    {
        RateLimiter::for('partner_api', function (Request $request) {
            return Limit::perMinute(1)->by('test-key')
                ->response(fn ($request, $headers) => response()->json([
                    'status' => 'error',
                    'error_code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many requests',
                ], 429, $headers));
        });

        $this->withHeader('Authorization', 'Bearer bad-key')->postJson('/api/v1/partner/customers', [])->assertStatus(401);
        $this->withHeader('Authorization', 'Bearer bad-key')
            ->postJson('/api/v1/partner/customers', [])
            ->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_partner_login_generates_token_and_can_access_partner_api(): void
    {
        $partner = Partner::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Partner OAuth',
            'slug' => 'partner-oauth',
            'email' => 'oauth@test.local',
            'status' => 'active',
            'is_active' => true,
            'password' => bcrypt('Secret12345!'),
        ]);
        Role::query()->firstOrCreate(['name' => 'partner', 'guard_name' => 'web']);
        $partner->syncRoles(['partner']);

        $product = Product::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'OAuth Product',
            'slug' => 'oauth-product',
            'status' => 'active',
            'cover_duration_options' => [12],
        ]);
        \DB::table('partner_products')->insert([
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'status' => 'active',
            'partner_price' => 25.00,
            'partner_currency' => 'USD',
            'activated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tokenResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $partner->email,
            'password' => 'Secret12345!',
        ])->assertOk();

        $token = $tokenResponse->json('data.token');
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/partner/products')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.0.partner_currency', 'USD');
    }

    public function test_partner_purchase_endpoint_auto_calculates_beneficiary_age(): void
    {
        $partner = Partner::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Swap Circle',
            'slug' => 'swap-circle',
            'email' => 'swap-circle@test.local',
            'status' => 'active',
            'is_active' => true,
            'password' => bcrypt('Secret12345!'),
        ]);
        Role::query()->firstOrCreate(['name' => 'partner', 'guard_name' => 'web']);
        $partner->syncRoles(['partner']);
        Role::query()->firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $product = Product::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => 'Nigerian Beneficiary Community Product',
            'slug' => 'nigerian-beneficiary-community-product',
            'status' => 'active',
            'cover_duration_options' => [1, 12],
        ]);

        \DB::table('partner_products')->insert([
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'status' => 'active',
            'partner_currency' => 'NGN',
            'activated_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ProductField::query()->create([
            'product_id' => $product->id,
            'name' => 'beneficiary_date_of_birth',
            'label' => 'Beneficiary Date of Birth',
            'type' => 'date',
            'is_required' => true,
            'sort_order' => 1,
        ]);
        ProductField::query()->create([
            'product_id' => $product->id,
            'name' => 'beneficiary_age',
            'label' => 'Beneficiary Age',
            'type' => 'number',
            'is_required' => true,
            'sort_order' => 2,
        ]);
        ProductField::query()->create([
            'product_id' => $product->id,
            'name' => 'first_name',
            'label' => 'First Name',
            'type' => 'text',
            'is_required' => true,
            'sort_order' => 3,
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $partner->email,
            'password' => 'Secret12345!',
        ])->assertOk()->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/partner/purchases', [
                'product_id' => $product->id,
                'customer_data' => [
                    'first_name' => 'Ayo',
                    'last_name' => 'Balogun',
                    'email' => 'ayo@example.com',
                    'cover_start_date' => now()->toDateString(),
                    'cover_duration' => 'annual',
                    'beneficiary_date_of_birth' => now()->subYears(25)->toDateString(),
                ],
                'payment' => [
                    'amount' => 10999.00,
                    'currency' => 'NGN',
                    'payment_date' => now()->toIso8601String(),
                    'transaction_reference' => 'SWAP-REF-1001',
                ],
            ])
            ->assertCreated();

        $customer = \App\Models\Customer::query()->latest('id')->first();
        $this->assertNotNull($customer);
        $this->assertSame(12, (int) ($customer->cover_duration_months ?? 0));
        $this->assertSame(25, (int) ($customer->submitted_data['beneficiary_age'] ?? 0));
    }
}
