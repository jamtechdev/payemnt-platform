<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePartnerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        // Backward compatibility for older forms still sending `email`/`phone`.
        $this->merge([
            'contact_email' => $this->input('contact_email', $this->input('email')),
            'contact_phone' => $this->input('contact_phone', $this->input('phone')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255', 'unique:partners,contact_email'],
            'contact_phone' => ['required', 'string', 'max:40'],
            'partner_code'  => ['nullable', 'string', 'max:40', 'unique:partners,partner_code'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'website_url' => ['nullable', 'url', 'max:255'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
