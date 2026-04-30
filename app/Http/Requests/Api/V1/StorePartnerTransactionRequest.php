<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StorePartnerTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_number' => ['required', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'product_code' => ['required', 'string', 'max:40'],
            'cover_duration' => ['required', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,suspended,pending'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'date_added' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
        ];
    }
}
