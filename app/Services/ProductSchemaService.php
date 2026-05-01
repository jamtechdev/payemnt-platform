<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;

class ProductSchemaService
{
    public function generate(Product $product): array
    {
        $fields = $product->fields()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($field): array => [
                'field_key' => $field->field_key,
                'label' => $field->label,
                'type' => $field->field_type,
                'required' => (bool) $field->is_required,
                'options' => $field->options ?? [],
            ])
            ->values()
            ->all();

        return [
            'product_code' => $product->product_code,
            'endpoint_base' => "/api/v1/products/{$product->product_code}",
            'transaction_payload' => [
                'transaction_number' => 'string|required',
                'customer_name' => 'string|required',
                'customer_email' => 'string|required|email',
                'phone' => 'string|nullable',
                'product_code' => 'string|required',
                'cover_duration' => 'string|required',
                'status' => 'active|suspended|pending',
            ],
            'product_fields' => $fields,
            'validation_rules' => $product->validation_rules ?? [],
        ];
    }
}
