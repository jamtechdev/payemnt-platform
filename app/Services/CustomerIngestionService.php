<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Partner;

class CustomerIngestionService
{
    public function __construct(private readonly PartnerTransactionService $partnerTransactionService)
    {
    }

    public function createCustomer(array $validated, Partner $partner): Customer
    {
        return $this->partnerTransactionService->createCustomerWithInitialPayment($validated, $partner);
    }
}
