<?php

namespace App\Listeners;

use App\Events\CustomerCreated;
use App\Models\AuditLog;

class CustomerCreatedListener
{
    public function handle(CustomerCreated $event): void
    {
        AuditLog::query()->create([
            'user_id' => null,
            'user_type' => null,
            'action' => 'api_customer_created',
            'model_type' => $event->customer::class,
            'model_id' => $event->customer->id,
            'old_values' => [],
            'new_values' => $event->customer->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => ['partner_id' => $event->partner->id],
        ]);
    }
}
