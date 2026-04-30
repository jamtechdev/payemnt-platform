<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

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
            'partner_code' => ['required', 'string', 'max:40', 'unique:partners,partner_code'],
            'name' => ['required', 'string', 'max:255'],
            'partner_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:active,inactive,suspended'],
        ];
    }
}
