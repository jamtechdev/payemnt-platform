<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePartnerProductAccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:active,inactive'],
            'partner_price' => ['nullable', 'numeric', 'min:0'],
            'partner_currency' => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
        ];
    }
}
