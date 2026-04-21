<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

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
            'is_enabled' => ['required', 'boolean'],
            'partner_price' => ['nullable', 'numeric', 'min:0'],
            'partner_currency' => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'cover_duration_days_override' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
