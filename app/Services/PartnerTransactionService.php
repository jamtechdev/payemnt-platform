<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Partner;
use App\Repositories\PaymentRepository;

class PartnerTransactionService
{
    public function __construct(private readonly PaymentRepository $paymentRepository)
    {
    }

    public function appendPayment(Customer $customer, Partner $partner, array $payment): void
    {
        $record = $this->paymentRepository->create([
            'customer_id' => $customer->id,
            'partner_id' => $partner->id,
            'product_id' => $customer->product_id,
            'amount' => (float) $payment['amount'],
            'currency' => strtoupper((string) $payment['currency']),
            'paid_at' => (string) $payment['paid_at'],
            'transaction_reference' => $payment['transaction_reference'] ?? null,
            'status' => $payment['status'] ?? 'success',
            'metadata' => $payment['metadata'] ?? [],
        ]);

        $customer->update(['last_payment_date' => $record->paid_at]);
    }
}
