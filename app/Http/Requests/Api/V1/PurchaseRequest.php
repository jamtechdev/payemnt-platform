<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $partner = $this->attributes->get('partner');
        if ($partner) {
            $this->merge(['partner_id' => $partner->partner_code]);
        }
    }

    public function rules(): array
    {
        $partner = $this->attributes->get('partner');

        return [
            'partner_id' => ['required', 'string', Rule::in([$partner?->partner_code])],
            'product_id' => ['required', 'string', 'exists:products,product_code'],
            'customer' => ['required', 'array'],
            'customer.first_name' => ['required', 'string', 'max:120'],
            'customer.last_name' => ['required', 'string', 'max:120'],
            'customer.date_of_birth' => ['required', 'date', 'before:today'],
            'customer.age' => ['nullable', 'integer', 'min:0'],
            'customer.gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'customer.address' => ['required', 'string', 'max:255'],
            'customer.cover_start_date' => ['required', 'date'],
            'customer.cover_duration' => ['required', Rule::in(['monthly', 'annual'])],
            'payment' => ['required', 'array'],
            'payment.amount' => ['required', 'numeric', 'min:0'],
            'payment.currency' => ['required', 'string', 'size:3'],
            'payment.transaction_reference' => ['required', 'string', 'max:120', 'unique:payments,transaction_reference'],
        ];
    }
}
