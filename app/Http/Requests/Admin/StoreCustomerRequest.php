<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'partner_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'cover_start_date' => ['required', 'date'],
            'cover_duration_months' => ['required', 'integer', 'min:1'],
            'status' => ['nullable', 'in:active,expired,cancelled'],
            'submitted_data' => ['nullable', 'array'],
        ];
    }
}
