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
            ->whereNotIn('product_code', ['INSURETECH_SWAP_PROTECT'])
            ->get()
            ->each(function (Product $product): void {
                $product->partners()->detach();
                $product->delete();
            });

        $partner = Partner::query()->updateOrCreate(
            ['partner_code' => 'SWAP_CIRCLE'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Swap',
                'partner_name' => 'Swap',
                'slug' => Str::slug('Swap'),
                'contact_email' => 'integrations@swap.example',
                'email' => 'integrations@swap.example',
                'contact_phone' => '+2348000000000',
                'phone' => '+2348000000000',
                'status' => 'active',
            ]
        );

        $product = Product::query()->updateOrCreate(
            ['product_code' => 'INSURETECH_SWAP_PROTECT'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'InsureTech Swap Protect',
                'product_name' => 'InsureTech Swap Protect',
                'slug' => Str::slug('InsureTech Swap Protect'),
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
