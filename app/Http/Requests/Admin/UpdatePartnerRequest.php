<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $partnerId = $this->route('partner')?->id;

        return [
            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'required', 'email', 'max:255', 'unique:partners,contact_email,'.$partnerId],
            'contact_phone' => ['sometimes', 'required', 'string', 'max:40'],
            'status'        => ['sometimes', 'in:active,inactive'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ];
    }
}
