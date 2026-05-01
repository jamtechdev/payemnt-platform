<?php

declare(strict_types=1);

namespace Tests\Feature\Feature;

use App\Models\Partner;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductDistributionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_verify_token(): void
    {
        $partner = Partner::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_code' => 'PRT_VERIFY',
            'name' => 'Verify Partner',
            'slug' => 'verify-partner',
            'status' => 'active',
        ]);

        $token = $partner->createToken('partner-api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/verify-token')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.partner.code', 'PRT_VERIFY');
    }

    public function test_partner_can_submit_product_policy_payload(): void
    {
        $partner = Partner::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_code' => 'PRT_SUBMIT',
            'name' => 'Submit Partner',
            'slug' => 'submit-partner',
            'status' => 'active',
        ]);

        $product = Product::query()->create([
            'uuid' => (string) Str::uuid(),
            'product_code' => 'PROD_SUBMIT',
            'name' => 'Family Cover',
            'slug' => 'family-cover',
            'cover_duration_mode' => 'custom',
            'default_cover_duration_days' => 365,
            'status' => 'active',
        ]);
        $partner->products()->sync([$product->id => ['is_enabled' => true]]);

        $token = $partner->createToken('partner-api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/products/{$product->product_code}/submit", [
                'transaction_number' => 'TXN-001',
                'customer_name' => 'John Doe',
                'customer_email' => 'john@example.com',
                'phone' => '+2348000000000',
                'cover_duration' => '12 months',
                'status' => 'pending',
                'kyc' => ['id_type' => 'national_id', 'id_number' => 'ABC123'],
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.transaction_number', 'TXN-001');
    }
}
