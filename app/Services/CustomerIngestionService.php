<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Partner;
use App\Models\Product;
use App\Repositories\CustomerRepository;
use App\Repositories\PaymentRepository;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerIngestionService
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly DynamicProductValidationService $dynamicProductValidationService,
    ) {
    }

    public function ingest(Partner $partner, Product $product, array $payload): Customer
    {
        $customerData = (array) ($payload['customer_data'] ?? []);
        $paymentData = (array) ($payload['payment'] ?? []);

        $customerData = $this->dynamicProductValidationService->validateAndNormalize($product, $customerData);

        return DB::transaction(function () use ($partner, $product, $customerData, $paymentData): Customer {
            $startDate = Carbon::parse((string) Arr::get($customerData, 'start_date', now()->toDateString()));
            $coverDuration = (int) Arr::get($customerData, 'cover_duration_days', $product->default_cover_duration_days);
            if ($coverDuration < 1) {
                throw ValidationException::withMessages(['customer_data.cover_duration_days' => ['Cover duration must be at least 1 day.']]);
            }

            $customer = $this->customerRepository->create([
                'partner_id' => $partner->id,
                'product_id' => $product->id,
                'external_customer_id' => Arr::get($customerData, 'external_customer_id'),
                'first_name' => (string) Arr::get($customerData, 'first_name'),
                'last_name' => (string) Arr::get($customerData, 'last_name'),
                'email' => Arr::get($customerData, 'email'),
                'phone' => Arr::get($customerData, 'phone'),
                'status' => 'active',
                'start_date' => $startDate->toDateString(),
                'cover_duration_days' => $coverDuration,
                'cover_end_date' => $startDate->copy()->addDays($coverDuration)->toDateString(),
                'customer_since' => Arr::get($customerData, 'customer_since', now()->toDateString()),
                'customer_data' => $customerData,
            ]);

            foreach ($customerData as $key => $value) {
                $customer->meta()->create([
                    'meta_key' => (string) $key,
                    'meta_value' => is_array($value) ? $value : ['value' => $value],
                ]);
            }

            $payment = $this->paymentRepository->create([
                'customer_id' => $customer->id,
                'partner_id' => $partner->id,
                'product_id' => $product->id,
                'amount' => (float) $paymentData['amount'],
                'currency' => strtoupper((string) $paymentData['currency']),
                'paid_at' => (string) $paymentData['paid_at'],
                'transaction_reference' => Arr::get($paymentData, 'transaction_reference'),
                'status' => Arr::get($paymentData, 'status', 'success'),
                'metadata' => Arr::get($paymentData, 'metadata', []),
            ]);

            $customer->update(['last_payment_date' => $payment->paid_at]);

            return $customer->fresh(['partner', 'product', 'payments']);
        });
    }
}
