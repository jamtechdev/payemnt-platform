<?php

declare(strict_types=1);

namespace Tests\Feature\Feature;

use App\Models\Partner;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PartnerApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_bearer_token_returns_401(): void
    {
        $this->postJson('/api/v1/customers', [])
            ->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    public function test_partner_can_submit_customer_with_dynamic_fields(): void
    {
        $partner = Partner::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_code' => 'PARTNER_001',
            'name' => 'Swap Circle',
            'slug' => 'swap-circle',
            'status' => 'active',
        ]);

        $product = Product::query()->create([
            'uuid' => (string) Str::uuid(),
            'product_code' => 'PROD_123',
            'name' => 'Standard Cover',
            'slug' => 'standard-cover',
            'cover_duration_mode' => 'custom',
            'default_cover_duration_days' => 365,
            'status' => 'active',
        ]);
        $partner->products()->sync([$product->id => ['is_enabled' => true]]);

        $product->fields()->createMany([
            ['field_key' => 'first_name', 'label' => 'First Name', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 1],
            ['field_key' => 'last_name', 'label' => 'Last Name', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 2],
            ['field_key' => 'start_date', 'label' => 'Start Date', 'field_type' => 'date', 'is_required' => true, 'sort_order' => 3],
            ['field_key' => 'cover_duration_days', 'label' => 'Duration', 'field_type' => 'number', 'is_required' => true, 'sort_order' => 4],
            ['field_key' => 'customer_since', 'label' => 'Customer Since', 'field_type' => 'date', 'is_required' => false, 'default_value' => ['value' => now()->toDateString()], 'sort_order' => 5],
        ]);

        $token = $partner->createToken('partner-api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/customers', [
                'partner_id' => 'PARTNER_001',
                'product_id' => 'PROD_123',
                'customer_data' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'start_date' => now()->toDateString(),
                    'cover_duration_days' => 365,
                ],
                'payment' => [
                    'amount' => 120.50,
                    'currency' => 'USD',
                    'paid_at' => now()->toIso8601String(),
                    'transaction_reference' => 'TX-'.uniqid(),
                    'status' => 'success',
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.customer.partner_id', 'PARTNER_001');
    }
}
