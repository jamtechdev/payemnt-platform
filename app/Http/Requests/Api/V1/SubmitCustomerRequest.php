<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class SubmitCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $partner = $this->attributes->get('partner');

        if ($partner && ! $this->filled('partner_id')) {
            $this->merge(['partner_id' => $partner->partner_code]);
        }

        $customerData = (array) $this->input('customer_data', []);
        $duration = Arr::get($customerData, 'cover_duration');
        if (! isset($customerData['cover_duration_months']) && is_string($duration)) {
            $normalized = strtolower(trim($duration));
            if ($normalized === 'monthly') {
                $customerData['cover_duration_months'] = 1;
            }
            if ($normalized === 'annual') {
                $customerData['cover_duration_months'] = 12;
            }
        }

        $dob = Arr::get($customerData, 'beneficiary_date_of_birth');
        if (is_string($dob) && trim($dob) !== '') {
            try {
                $customerData['beneficiary_age'] = Carbon::parse($dob)->age;
            } catch (\Throwable) {
                // Invalid dates are handled in validation.
            }
        }

        if (! Arr::has($customerData, 'first_name') && is_string(Arr::get($customerData, 'beneficiary_first_name'))) {
            $customerData['first_name'] = Arr::get($customerData, 'beneficiary_first_name');
        }
        if (! Arr::has($customerData, 'last_name') && is_string(Arr::get($customerData, 'beneficiary_surname'))) {
            $customerData['last_name'] = Arr::get($customerData, 'beneficiary_surname');
        }

        $this->merge(['customer_data' => $customerData]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $partner = $this->attributes->get('partner');

        return [
            'partner_id' => [
                'required',
                'string',
                Rule::in([(string) $partner?->partner_code]),
            ],
            'product_id' => [
                'required',
                'string',
                Rule::exists('products', 'product_code'),
            ],
            'customer_data' => ['required', 'array'],
            'payment' => ['required', 'array'],
            'payment.amount' => ['required', 'numeric', 'min:0'],
            'payment.currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'payment.paid_at' => ['required', 'date'],
            'payment.transaction_reference' => ['required', 'string', 'unique:payments,transaction_reference'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'status' => 'error',
            'error_code' => 'VALIDATION_ERROR',
            'message' => 'Request validation failed.',
            'details' => $validator->errors(),
        ], 422));
    }

}
