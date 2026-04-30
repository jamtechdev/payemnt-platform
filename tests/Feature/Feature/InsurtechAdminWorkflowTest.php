<?php

declare(strict_types=1);

namespace Tests\Feature\Feature;

use App\Models\Customer;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InsurtechAdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_fetch_generated_product_schema(): void
    {
        $partner = Partner::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_code' => 'SWAP',
            'name' => 'Swap',
            'slug' => 'swap',
            'contact_email' => 'swap@example.com',
            'contact_phone' => '+2348000000000',
            'status' => 'active',
        ]);

        $product = Product::query()->create([
            'uuid' => (string) Str::uuid(),
            'product_code' => 'SWAP_BEN_COMM',
            'name' => 'Swap Beneficiary Cover',
            'slug' => 'swap-beneficiary-cover',
            'status' => 'active',
        ]);
        $product->fields()->create([
            'field_key' => 'beneficiary_first_name',
            'label' => 'Beneficiary First Name',
            'field_type' => 'text',
            'is_required' => true,
            'sort_order' => 1,
        ]);
        $partner->products()->sync([$product->id => ['is_enabled' => true]]);
        $token = $partner->createToken('partner-api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/partner/products/'.$product->uuid.'/schema')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.product.product_code', 'SWAP_BEN_COMM')
            ->assertJsonPath('data.request_schema.transaction_payload.transaction_number', 'string|required');
    }

    public function test_transaction_number_is_idempotent_per_partner(): void
    {
        $partner = Partner::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_code' => 'P_ONE',
            'name' => 'Partner One',
            'slug' => 'partner-one',
            'contact_email' => 'p1@example.com',
            'contact_phone' => '+111111111',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'uuid' => (string) Str::uuid(),
            'product_code' => 'PROD_ONE',
            'name' => 'Product One',
            'slug' => 'product-one',
            'status' => 'active',
        ]);
        $partner->products()->sync([$product->id => ['is_enabled' => true]]);
        $customer = Customer::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_id' => $partner->id,
            'product_id' => $product->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'start_date' => now()->toDateString(),
            'cover_duration_days' => 30,
            'customer_since' => now()->toDateString(),
            'status' => 'active',
        ]);
        $token = $partner->createToken('partner-api')->plainTextToken;

        $payload = [
            'transaction_number' => 'TXN-101',
            'customer_name' => 'John Doe',
            'customer_email' => $customer->email,
            'product_code' => $product->product_code,
            'cover_duration' => '12_months',
            'status' => 'active',
            'date_added' => now()->toDateTimeString(),
        ];

        $this->withHeader('Authorization', "Bearer {$token}")
            ->withHeader('Idempotency-Key', 'TXN-101')
            ->postJson('/api/v1/transactions', $payload)
            ->assertOk();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->withHeader('Idempotency-Key', 'TXN-101')
            ->postJson('/api/v1/transactions', $payload)
            ->assertOk();

        $this->assertSame(1, Payment::query()->where('partner_id', $partner->id)->where('transaction_number', 'TXN-101')->count());
    }

    public function test_partner_product_listing_hides_guide_price(): void
    {
        $partner = Partner::query()->create([
            'uuid' => (string) Str::uuid(),
            'partner_code' => 'P_HIDE',
            'name' => 'Partner Hidden',
            'slug' => 'partner-hidden',
            'contact_email' => 'hidden@example.com',
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'uuid' => (string) Str::uuid(),
            'product_code' => 'PROD_HIDE',
            'name' => 'Hidden Guide Product',
            'product_name' => 'Hidden Guide Product',
            'slug' => 'hidden-guide-product',
            'status' => 'active',
            'guide_price' => 999.99,
        ]);
        $partner->products()->sync([$product->id => ['is_enabled' => true]]);
        $token = $partner->createToken('partner-api')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/partner/products')
            ->assertOk()
            ->assertJsonMissingPath('data.0.guide_price');
    }
}
