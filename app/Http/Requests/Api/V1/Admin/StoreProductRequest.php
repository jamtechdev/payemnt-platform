<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'partner_id' => ['required', 'integer'],
            'partner_code' => ['required', 'string', 'max:40'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'product_code' => ['required', 'string', 'max:40', 'unique:products,product_code'],
            'name' => ['required', 'string', 'max:255'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'guide_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
            'default_cover_duration_days' => ['nullable', 'integer', 'min:1'],
            'fields' => ['array'],
            'fields.*.field_key' => ['required_without:fields.*.name', 'string'],
            'fields.*.name' => ['required_without:fields.*.field_key', 'string'],
            'fields.*.label' => ['required_with:fields', 'string'],
            'fields.*.field_type' => ['required_without:fields.*.type', 'in:text,number,date,dropdown,boolean,email,phone'],
            'fields.*.type' => ['required_without:fields.*.field_type', 'in:text,number,date,dropdown,boolean,email,phone'],
            'fields.*.is_required' => ['boolean'],
            'fields.*.is_filterable' => ['boolean'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.validation_rule' => ['nullable', 'string'],
            'fields.*.sort_order' => ['integer'],
        ];
    }
}
