<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['sometimes', 'string', 'max:255'],
            'customer_email' => ['sometimes', 'email', 'max:255'],
            'cover_duration' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', 'in:active,suspended,pending'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
