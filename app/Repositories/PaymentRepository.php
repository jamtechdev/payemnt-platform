<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Payment;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class PaymentRepository
{
    public function create(array $payload): Payment
    {
        return Payment::query()->create($payload);
    }

    public function revenueByProduct(?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        return Payment::query()
            ->selectRaw('product_id, currency, SUM(amount) as total_revenue')
            ->when($from, fn ($query) => $query->where('paid_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('paid_at', '<=', $to))
            ->where('status', 'success')
            ->groupBy('product_id', 'currency')
            ->get();
    }
}
