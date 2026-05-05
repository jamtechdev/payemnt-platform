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
            'is_enabled'                   => ['required', 'boolean'],
            'currency_id'                  => ['required', 'integer', 'exists:currencies,id'],
            'base_price'                   => ['required', 'numeric', 'min:0'],
            'guide_price'                  => ['nullable', 'numeric', 'min:0'],
            'cover_duration_days_override' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
