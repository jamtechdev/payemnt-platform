<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InsurtechDummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // Keep only one partner setup; do not seed transactions/customers.
        Payment::query()->delete();
        Customer::query()->delete();

        Partner::query()
            ->where('partner_code', '!=', 'SWAP_CIRCLE')
            ->get()
            ->each(function (Partner $partner): void {
                $partner->products()->detach();
                $partner->tokens()->delete();
                $partner->delete();
            });

        Product::query()
            ->whereNotIn('product_code', ['NIGERIA_BENEFICIARY_COMMUNITY'])
            ->get()
            ->each(function (Product $product): void {
                $product->partners()->detach();
                $product->delete();
            });

        $partner = Partner::query()->updateOrCreate(
            ['partner_code' => 'SWAP_DUMMY'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Swap Dummy Partner',
                'partner_name' => 'Swap Dummy Partner',
                'slug' => Str::slug('Swap Dummy Partner'),
                'contact_email' => 'swap.dummy@example.com',
                'email' => 'swap.dummy@example.com',
                'contact_phone' => '+2348001000001',
                'phone' => '+2348001000001',
                'status' => 'active',
            ]
        );

        $product = Product::query()->updateOrCreate(
            ['product_code' => 'NIGERIA_BENEFICIARY_COMMUNITY'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Nigerian Beneficiary Community Product',
                'product_name' => 'Nigerian Beneficiary Community Product',
                'slug' => Str::slug('Nigerian Beneficiary Community Product'),
                'description' => 'Single dummy product for partner integration testing.',
                'status' => 'active',
                'guide_price' => 100.00,
                'price' => 100.00,
                'base_price' => 100.00,
                'cover_duration_mode' => 'custom',
                'default_cover_duration_days' => 30,
                'cover_duration_options' => [30, 365],
            ]
        );

        $partner->products()->sync([
            $product->id => [
                'is_enabled' => true,
                'partner_price' => 100.00,
                'partner_currency' => 'USD',
                'cover_duration_days_override' => 30,
            ],
        ]);

        $partner->tokens()->delete();
        $token = $partner->createToken('dummy-partner-token')->plainTextToken;
        $this->command?->info("Partner {$partner->partner_code} token: {$token}");
    }
}
