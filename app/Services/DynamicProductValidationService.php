<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DynamicProductValidationService
{
    public function validateAndNormalize(Product $product, array $customerData): array
    {
        $rules = [];
        $normalized = $customerData;

        foreach ($product->fields as $field) {
            if (! array_key_exists($field->field_key, $normalized) && ! empty($field->default_value)) {
                $normalized[$field->field_key] = $field->default_value['value'] ?? $field->default_value;
            }

            $fieldRules = [$field->is_required ? 'required' : 'nullable'];

            $fieldRules[] = match ($field->field_type) {
                'email' => 'email',
                'number' => 'numeric',
                'date' => 'date',
                'datetime' => 'date',
                'boolean' => 'boolean',
                'phone' => 'regex:/^\+?[0-9\-\s]{7,20}$/',
                default => 'string',
            };

            if ($field->field_type === 'dropdown' && is_array($field->options) && $field->options !== []) {
                $fieldRules[] = Rule::in(array_values($field->options));
            }

            if ($field->validation_rule) {
                $fieldRules[] = $field->validation_rule;
            }

            $rules[$field->field_key] = $fieldRules;
        }

        $validator = Validator::make($normalized, $rules);
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
