<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use App\Models\Customer;
use App\Models\Partner;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Customer $customer,
        public Partner $partner,
    ) {}
}
