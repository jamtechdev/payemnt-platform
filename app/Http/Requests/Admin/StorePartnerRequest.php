<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255', 'unique:partners,contact_email'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'partner_code'  => ['nullable', 'string', 'max:40', 'unique:partners,partner_code'],
        ];
    }
}
