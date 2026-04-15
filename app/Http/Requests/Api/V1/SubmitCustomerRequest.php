<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Product;
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
                Rule::in([$partner?->id]),
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
            'payment.payment_date' => ['required', 'date', 'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|[+\-]\d{2}:\d{2})$/'],
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
                if ($field->is_required && $value === null) {
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
