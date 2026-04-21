<?php

declare(strict_types=1);

namespace Tests\Feature\Feature;

use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SwapCircleIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_seeder_creates_swap_circle_products_fields_and_pricing(): void
    {
        $this->seed(PlatformSeeder::class);

        $partner = Partner::query()->where('partner_code', 'SWAP_CIRCLE')->firstOrFail();
        $nigeriaProduct = Product::query()->where('product_code', 'NIGERIA_BENEFICIARY_COMMUNITY')->firstOrFail();
        $ghanaProduct = Product::query()->where('product_code', 'GHANA_BENEFICIARY_COMMUNITY')->firstOrFail();

        $this->assertSame('Nigerian Beneficiary Community Product', $nigeriaProduct->name);
        $this->assertSame('Ghana Beneficiary Community Product', $ghanaProduct->name);

        $expectedFieldKeys = [
            'beneficiary_first_name',
            'beneficiary_surname',
            'beneficiary_date_of_birth',
            'beneficiary_age',
            'beneficiary_gender',
            'beneficiary_address',
            'cover_start_date',
            'cover_duration',
        ];

        $this->assertSame($expectedFieldKeys, $nigeriaProduct->fields()->orderBy('sort_order')->pluck('field_key')->all());
        $this->assertSame($expectedFieldKeys, $ghanaProduct->fields()->orderBy('sort_order')->pluck('field_key')->all());

        $nigeriaPivot = $partner->products()->where('products.id', $nigeriaProduct->id)->firstOrFail()->pivot;
        $ghanaPivot = $partner->products()->where('products.id', $ghanaProduct->id)->firstOrFail()->pivot;

        $this->assertTrue((bool) $nigeriaPivot->is_enabled);
        $this->assertSame('NGN', $nigeriaPivot->partner_currency);
        $this->assertSame('100.00', (string) $nigeriaPivot->partner_price);

        $this->assertTrue((bool) $ghanaPivot->is_enabled);
        $this->assertSame('GHS', $ghanaPivot->partner_currency);
        $this->assertSame('25.00', (string) $ghanaPivot->partner_price);
    }

    public function test_purchase_endpoint_stores_customer_and_derives_age_from_date_of_birth(): void
    {
        $this->seed(PlatformSeeder::class);

        $partner = Partner::query()->where('partner_code', 'SWAP_CIRCLE')->firstOrFail();
        $token = $partner->createToken('partner-api')->plainTextToken;

        $payload = [
            'partner_id' => 'SWAP_CIRCLE',
            'product_id' => 'NIGERIA_BENEFICIARY_COMMUNITY',
            'customer' => [
                'first_name' => 'Amina',
                'last_name' => 'Okafor',
                'date_of_birth' => '1994-04-15',
                'gender' => 'female',
                'address' => '12 Admiralty Way, Lekki, Lagos',
                'cover_start_date' => '2026-05-01',
                'cover_duration' => 'monthly',
                'external_customer_id' => 'SWAP-NG-0001',
            ],
            'payment' => [
                'amount' => 100.00,
                'currency' => 'NGN',
                'transaction_reference' => 'SWAP-TXN-NG-0001',
            ],
        ];

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/purchase', $payload)
            ->assertCreated()
            ->assertJsonPath('status', 'success');

        $customer = Customer::query()->where('external_customer_id', 'SWAP-NG-0001')->firstOrFail();
        $this->assertSame('Amina', $customer->first_name);
        $this->assertSame(Carbon::parse('1994-04-15')->age, $customer->age);
        $this->assertSame('monthly', $customer->cover_duration);
    }

    public function test_purchase_endpoint_rejects_payload_when_customer_age_does_not_match_dob(): void
    {
        $this->seed(PlatformSeeder::class);

        $partner = Partner::query()->where('partner_code', 'SWAP_CIRCLE')->firstOrFail();
        $token = $partner->createToken('partner-api')->plainTextToken;

        $payload = [
            'partner_id' => 'SWAP_CIRCLE',
            'product_id' => 'GHANA_BENEFICIARY_COMMUNITY',
            'customer' => [
                'first_name' => 'Kwame',
                'last_name' => 'Mensah',
                'date_of_birth' => '1990-01-10',
                'age' => 99,
                'gender' => 'male',
                'address' => 'East Legon, Accra',
                'cover_start_date' => '2026-05-01',
                'cover_duration' => 'annual',
            ],
            'payment' => [
                'amount' => 25.00,
                'currency' => 'GHS',
                'transaction_reference' => 'SWAP-TXN-GH-0001',
            ],
        ];

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/purchase', $payload)
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonPath('details.customer.age.0', 'Customer age must match date_of_birth.');
    }

    public function test_customer_service_admin_cannot_view_product_pricing_in_customer_detail_api(): void
    {
        $this->seed(PlatformSeeder::class);

        Permission::findOrCreate('customers.view_detail', 'web');
        $customerServiceRole = Role::findOrCreate('customer_service', 'web');
        $customerServiceRole->givePermissionTo('customers.view_detail');

        $actor = User::factory()->create();
        $actor->assignRole($customerServiceRole);
        $actorToken = $actor->createToken('admin')->plainTextToken;

        $partner = Partner::query()->where('partner_code', 'SWAP_CIRCLE')->firstOrFail();
        $partnerToken = $partner->createToken('partner-api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$partnerToken}")
            ->postJson('/api/v1/purchase', [
                'partner_id' => 'SWAP_CIRCLE',
                'product_id' => 'NIGERIA_BENEFICIARY_COMMUNITY',
                'customer' => [
                    'first_name' => 'Bola',
                    'last_name' => 'Adebayo',
                    'date_of_birth' => '1998-08-08',
                    'gender' => 'female',
                    'address' => 'Ikeja, Lagos',
                    'cover_start_date' => '2026-05-01',
                    'cover_duration' => 'monthly',
                    'external_customer_id' => 'SWAP-NG-0002',
                ],
                'payment' => [
                    'amount' => 100.00,
                    'currency' => 'NGN',
                    'transaction_reference' => 'SWAP-TXN-NG-0002',
                ],
            ])->assertCreated();

        $customer = Customer::query()->where('external_customer_id', 'SWAP-NG-0002')->firstOrFail();

        $this->withHeader('Authorization', "Bearer {$actorToken}")
            ->getJson('/api/v1/admin/customers/'.$customer->id)
            ->assertOk()
            ->assertJsonPath('data.product_pricing', null);
    }
}
