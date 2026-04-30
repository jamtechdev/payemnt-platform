<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $products = [
            [
                'product_code' => 'NIG_BEN_COMM',
                'name' => 'Nigerian Beneficiary Community Product',
                'description' => 'Community beneficiary cover for Nigerian beneficiaries.',
                'country' => 'NG',
            ],
            [
                'product_code' => 'GHA_BEN_COMM',
                'name' => 'Ghana Beneficiary Community Product',
                'description' => 'Community beneficiary cover for Ghanaian beneficiaries.',
                'country' => 'GH',
            ],
        ];

        $fieldTemplates = [
            ['field_key' => 'beneficiary_first_name', 'label' => 'Beneficiary First name', 'field_type' => 'text', 'is_required' => true],
            ['field_key' => 'beneficiary_surname', 'label' => 'Beneficiary Surname', 'field_type' => 'text', 'is_required' => true],
            ['field_key' => 'beneficiary_date_of_birth', 'label' => 'Beneficiary Date of Birth', 'field_type' => 'date', 'is_required' => true],
            ['field_key' => 'beneficiary_age', 'label' => 'Beneficiary Age', 'field_type' => 'number', 'is_required' => false, 'validation_rule' => 'auto_calculated_from_beneficiary_date_of_birth'],
            ['field_key' => 'beneficiary_gender', 'label' => 'Beneficiary Gender', 'field_type' => 'dropdown', 'is_required' => true, 'options' => ['Male', 'Female', 'Other']],
            ['field_key' => 'beneficiary_address', 'label' => 'Beneficiary Address', 'field_type' => 'text', 'is_required' => true],
            ['field_key' => 'cover_start_date', 'label' => 'Cover start date', 'field_type' => 'date', 'is_required' => true],
            ['field_key' => 'cover_duration', 'label' => 'Cover Duration', 'field_type' => 'dropdown', 'is_required' => true, 'options' => ['Monthly', 'Annual']],
        ];

        foreach ($products as $productData) {
            $existing = DB::table('products')->where('product_code', $productData['product_code'])->first();

            if ($existing) {
                $productId = $existing->id;
                DB::table('products')->where('id', $productId)->update([
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'country' => $productData['country'],
                    'cover_duration_mode' => 'custom',
                    'cover_duration_options' => json_encode([30, 365]),
                    'default_cover_duration_days' => 365,
                    'status' => 'active',
                    'updated_at' => now(),
                ]);
            } else {
                $productId = DB::table('products')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'product_code' => $productData['product_code'],
                    'name' => $productData['name'],
                    'slug' => Str::slug($productData['name'].'-'.$productData['product_code']),
                    'description' => $productData['description'],
                    'country' => $productData['country'],
                    'cover_duration_mode' => 'custom',
                    'cover_duration_options' => json_encode([30, 365]),
                    'default_cover_duration_days' => 365,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($fieldTemplates as $index => $field) {
                DB::table('product_fields')->updateOrInsert(
                    ['product_id' => $productId, 'field_key' => $field['field_key']],
                    [
                        'label' => $field['label'],
                        'field_type' => $field['field_type'],
                        'is_required' => $field['is_required'],
                        'is_filterable' => true,
                        'options' => isset($field['options']) ? json_encode($field['options']) : null,
                        'validation_rule' => $field['validation_rule'] ?? null,
                        'sort_order' => $index,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        $codes = ['NIG_BEN_COMM', 'GHA_BEN_COMM'];
        $productIds = DB::table('products')->whereIn('product_code', $codes)->pluck('id')->all();
        if (!empty($productIds)) {
            DB::table('product_fields')->whereIn('product_id', $productIds)->delete();
            DB::table('products')->whereIn('id', $productIds)->delete();
        }
    }
};
