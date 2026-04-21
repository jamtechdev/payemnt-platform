<?php

declare(strict_types=1);

use App\Models\Partner;
use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $targetDefinitions = [
            [
                'product_code' => 'NIGERIA_BENEFICIARY_COMMUNITY',
                'name' => 'Nigerian Beneficiary Community Product',
                'country' => 'NG',
                'partner_price' => 100.00,
                'partner_currency' => 'NGN',
            ],
            [
                'product_code' => 'GHANA_BENEFICIARY_COMMUNITY',
                'name' => 'Ghana Beneficiary Community Product',
                'country' => 'GH',
                'partner_price' => 25.00,
                'partner_currency' => 'GHS',
            ],
        ];

        $obsoleteCodes = [
            'NIGERIA_COMMUNITY',
            'GHANA_COMMUNITY',
        ];

        Product::query()->whereIn('product_code', $obsoleteCodes)->get()->each(function (Product $product): void {
            $product->partners()->detach();
            $product->forceDelete();
        });

        $products = collect($targetDefinitions)->map(function (array $definition): Product {
            /** @var Product $product */
            $product = Product::query()->updateOrCreate(
                ['product_code' => $definition['product_code']],
                [
                    'name' => $definition['name'],
                    'slug' => Str::slug($definition['name']),
                    'country' => $definition['country'],
                    'cover_duration_mode' => 'custom',
                    'cover_duration_type' => 'custom',
                    'default_cover_duration_days' => 30,
                    'cover_duration_options' => [30, 365],
                    'status' => 'active',
                ],
            );

            if ($product->trashed()) {
                $product->restore();
            }

            $product->fields()->delete();
            $product->fields()->createMany([
                ['field_key' => 'beneficiary_first_name', 'label' => 'Beneficiary First Name', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 1],
                ['field_key' => 'beneficiary_surname', 'label' => 'Beneficiary Surname', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 2],
                ['field_key' => 'beneficiary_date_of_birth', 'label' => 'Beneficiary Date of Birth', 'field_type' => 'date', 'is_required' => true, 'sort_order' => 3],
                ['field_key' => 'beneficiary_age', 'label' => 'Beneficiary Age (Auto)', 'field_type' => 'number', 'is_required' => false, 'sort_order' => 4],
                ['field_key' => 'beneficiary_gender', 'label' => 'Beneficiary Gender', 'field_type' => 'dropdown', 'is_required' => true, 'options' => ['male', 'female', 'other'], 'sort_order' => 5],
                ['field_key' => 'beneficiary_address', 'label' => 'Beneficiary Address', 'field_type' => 'text', 'is_required' => true, 'sort_order' => 6],
                ['field_key' => 'cover_start_date', 'label' => 'Cover Start Date', 'field_type' => 'date', 'is_required' => true, 'sort_order' => 7],
                ['field_key' => 'cover_duration', 'label' => 'Cover Duration', 'field_type' => 'dropdown', 'is_required' => true, 'options' => ['monthly', 'annual'], 'sort_order' => 8],
            ]);

            return $product;
        });

        $swapCircle = Partner::query()->where('partner_code', 'SWAP_CIRCLE')->first();
        if (! $swapCircle) {
            return;
        }

        $syncPayload = $products->mapWithKeys(function (Product $product) use ($targetDefinitions): array {
            $definition = collect($targetDefinitions)->firstWhere('product_code', $product->product_code);

            return [
                $product->id => [
                    'is_enabled' => true,
                    'partner_price' => $definition['partner_price'] ?? null,
                    'partner_currency' => $definition['partner_currency'] ?? null,
                    'cover_duration_days_override' => 30,
                ],
            ];
        })->all();

        $swapCircle->products()->syncWithoutDetaching($syncPayload);
    }

    public function down(): void
    {
        Product::query()
            ->whereIn('product_code', ['NIGERIA_BENEFICIARY_COMMUNITY', 'GHANA_BENEFICIARY_COMMUNITY'])
            ->get()
            ->each(function (Product $product): void {
                $product->partners()->detach();
                $product->forceDelete();
            });
    }
};
