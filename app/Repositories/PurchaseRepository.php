<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Customer;
use App\Models\Payment;

class PurchaseRepository
{
    public function createCustomer(array $payload): Customer
    {
        return Customer::query()->create($payload);
    }

    public function createPayment(array $payload): Payment
    {
        return Payment::query()->create($payload);
    }
}
