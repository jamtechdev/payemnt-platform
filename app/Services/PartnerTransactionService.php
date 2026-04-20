<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\CustomerCreated;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Centralized transaction flow for partner-submitted product operations.
 * All partner API write operations must pass through this service.
 */
class PartnerTransactionService
{
    public function createCustomerWithInitialPayment(array $validated, Partner $partner): Customer
    {
        return DB::transaction(function () use ($validated, $partner): Customer {
            $customerData = $this->enrichBeneficiaryData($validated['customer_data']);

            $customer = Customer::query()->create([
                'partner_id' => $partner->id,
                'product_id' => (int) $validated['product_id'],
                'first_name' => (string) Arr::get($customerData, 'first_name'),
                'last_name' => (string) Arr::get($customerData, 'last_name'),
                'email' => (string) Arr::get($customerData, 'email'),
                'phone' => Arr::get($customerData, 'phone'),
                'cover_start_date' => (string) Arr::get($customerData, 'cover_start_date'),
                'cover_duration_months' => (int) Arr::get($customerData, 'cover_duration_months'),
                'status' => 'active',
                'submitted_data' => $customerData,
                'customer_since' => Arr::get($customerData, 'customer_since', now()->toDateString()),
            ]);
            if (Role::query()->where('name', 'customer')->where('guard_name', 'web')->exists()) {
                $customer->syncRoles(['customer']);
            }

            $this->recordPayment($customer, $partner, $validated['payment'], $validated);

            event(new CustomerCreated($customer, $partner));

            return $customer;
        });
    }

    public function appendPayment(Customer $customer, Partner $partner, array $payment, array $rawPayload): Payment
    {
        return DB::transaction(function () use ($customer, $partner, $payment, $rawPayload): Payment {
            return $this->recordPayment($customer, $partner, $payment, $rawPayload);
        });
    }

    public function updateCustomerStatus(Customer $customer, string $status): Customer
    {
        return DB::transaction(function () use ($customer, $status): Customer {
            $customer->update(['status' => $status]);

            return $customer->fresh();
        });
    }

    private function recordPayment(Customer $customer, Partner $partner, array $payment, array $rawPayload): Payment
    {
        return Payment::query()->create([
            'customer_id' => $customer->id,
            'partner_id' => $partner->id,
            'amount' => (float) $payment['amount'],
            'currency' => (string) $payment['currency'],
            'payment_date' => (string) $payment['payment_date'],
            'transaction_reference' => (string) $payment['transaction_reference'],
            'payment_status' => (string) Arr::get($payment, 'payment_status', 'success'),
            'raw_payload' => $rawPayload,
        ]);
    }

    /**
     * Ensure beneficiary age is always derived from date of birth.
     */
    private function enrichBeneficiaryData(array $customerData): array
    {
        $dob = Arr::get($customerData, 'beneficiary_date_of_birth');
        if (! is_string($dob) || trim($dob) === '') {
            return $customerData;
        }

        try {
            $customerData['beneficiary_age'] = Carbon::parse($dob)->age;
        } catch (\Throwable) {
            // Validation layer handles malformed DOB values.
        }

        return $customerData;
    }
}
