<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
            $this->merge(['partner_id' => $partner->id]);
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
                'integer',
                Rule::exists('users', 'id'),
                Rule::in([(int) $partner?->id]),
            ],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id'),
                Rule::exists('partner_products', 'product_id')->where(function ($query) use ($partner) {
                    $query->where('partner_id', $partner?->id)->where('status', 'active');
                }),
            ],
            'customer_data' => ['required', 'array'],
            'payment' => ['required', 'array'],
            'payment.amount' => ['required', 'numeric', 'min:0'],
            'payment.currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'payment.payment_date' => ['required', 'date'],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $product = Product::query()->with('fields')->find($this->input('product_id'));
            $payload = (array) $this->input('customer_data', []);

            if (! $product) {
                return;
            }

            foreach ($product->fields as $field) {
                $key = "customer_data.{$field->name}";
                $value = $payload[$field->name] ?? null;
                $isAutoCalculatedAge = $field->name === 'beneficiary_age';
                if ($field->is_required && $value === null && ! $isAutoCalculatedAge) {
                    $validator->errors()->add($key, 'This field is required.');
                    continue;
                }
                if ($value === null) {
                    continue;
                }

                match ($field->type) {
                    'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : $validator->errors()->add($key, 'Invalid email format.'),
                    'phone' => preg_match('/^\+?[0-9\-\s]{7,20}$/', (string) $value) ? null : $validator->errors()->add($key, 'Invalid phone format.'),
                    'number' => is_numeric($value) ? null : $validator->errors()->add($key, 'Must be numeric.'),
                    'date' => strtotime((string) $value) ? null : $validator->errors()->add($key, 'Invalid date.'),
                    'datetime' => strtotime((string) $value) ? null : $validator->errors()->add($key, 'Invalid datetime.'),
                    'dropdown' => in_array($value, $field->options, true) ? null : $validator->errors()->add($key, 'Value is not in allowed options.'),
                    default => null,
                };
            }
        });
    }
}
