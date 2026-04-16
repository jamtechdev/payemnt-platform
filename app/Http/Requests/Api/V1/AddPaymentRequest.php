<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class AddPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'payment_date' => ['required', 'date'],
            'transaction_reference' => ['required', 'string', 'unique:payments,transaction_reference'],
            'payment_status' => ['nullable', 'string', 'in:success,pending,failed,cancelled'],
        ];
    }
}
