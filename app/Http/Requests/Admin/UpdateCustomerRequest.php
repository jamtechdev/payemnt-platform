<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('uuid')
            ? \App\Models\Customer::query()->where('uuid', $this->route('uuid'))->value('id')
            : null;

        return [
            'partner_id' => ['sometimes', 'integer', Rule::exists('users', 'id')],
            'product_id' => ['sometimes', 'integer', Rule::exists('products', 'id')],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$customerId],
            'phone' => ['nullable', 'string', 'max:40'],
            'cover_start_date' => ['sometimes', 'date'],
            'cover_duration_months' => ['sometimes', 'integer', 'min:1'],
            'status' => ['nullable', 'in:active,expired,cancelled'],
            'submitted_data' => ['nullable', 'array'],
        ];
    }
}
