<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Currency;
use App\Models\Partner;
use App\Models\Product;
use App\Repositories\PurchaseRepository;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(private readonly PurchaseRepository $purchaseRepository)
    {
    }

    public function recordPurchase(Partner $partner, Product $product, array $payload): array
    {
        $customerData = (array) $payload['customer'];
        $paymentData  = (array) $payload['payment'];

        $pivot = $product->partners()
            ->where('partners.id', $partner->id)
            ->where('partner_product.is_enabled', true)
            ->first()?->pivot;

        if (! $pivot) {
            throw ValidationException::withMessages([
                'product_id' => ['Product is not assigned to this partner.'],
            ]);
        }

        $currency = Currency::find($pivot->currency_id);

        return DB::transaction(function () use ($partner, $product, $customerData, $paymentData, $pivot, $currency): array {
            $dob = Carbon::parse((string) $customerData['date_of_birth']);
            if (array_key_exists('age', $customerData) && $customerData['age'] !== null && (int) $customerData['age'] !== $dob->age) {
                throw ValidationException::withMessages([
                    'customer.age' => ['Customer age must match date_of_birth.'],
                ]);
            }

            $coverStartDate    = Carbon::parse((string) $customerData['cover_start_date']);
            $coverDurationDays = $customerData['cover_duration'] === 'monthly' ? 30 : 365;

            $customer = $this->purchaseRepository->createCustomer([
                'partner_id'           => $partner->id,
                'product_id'           => $product->id,
                'first_name'           => (string) $customerData['first_name'],
                'last_name'            => (string) $customerData['last_name'],
                'date_of_birth'        => $dob->toDateString(),
                'age'                  => $dob->age,
                'gender'               => (string) $customerData['gender'],
                'address'              => (string) $customerData['address'],
                'cover_start_date'     => $coverStartDate->toDateString(),
                'cover_duration'       => (string) $customerData['cover_duration'],
                'start_date'           => $coverStartDate->toDateString(),
                'cover_duration_days'  => $coverDurationDays,
                'cover_end_date'       => $coverStartDate->copy()->addDays($coverDurationDays)->toDateString(),
                'customer_since'       => now()->toDateString(),
                'customer_data'        => $customerData,
                'external_customer_id' => Arr::get($customerData, 'external_customer_id'),
            ]);

            $payment = $this->purchaseRepository->createPayment([
                'customer_id'           => $customer->id,
                'partner_id'            => $partner->id,
                'product_id'            => $product->id,
                'amount'                => (float) $paymentData['amount'],
                'currency'              => $currency?->code ?? strtoupper((string) $paymentData['currency']),
                'base_price'            => $pivot->base_price,
                'guide_price'           => $pivot->guide_price,
                'paid_at'               => now(),
                'transaction_reference' => (string) $paymentData['transaction_reference'],
                'status'                => 'success',
                'metadata'              => $paymentData,
            ]);

            $customer->update(['last_payment_date' => $payment->paid_at]);

            return [
                'customer_id'       => $customer->customer_code,
                'customer_model_id' => $customer->id,
            ];
        });
    }
}
