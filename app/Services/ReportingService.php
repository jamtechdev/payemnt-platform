<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportingService
{
    public function customerCountPerPartner(?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        return Customer::query()
            ->select('partner_id', DB::raw('COUNT(*) as customer_count'))
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->groupBy('partner_id')
            ->with('partner:id,name,partner_code')
            ->get();
    }

    public function customerCountPerProduct(?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        return Customer::query()
            ->select('product_id', DB::raw('COUNT(*) as customer_count'))
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->groupBy('product_id')
            ->with('product:id,name,product_code')
            ->get();
    }

    public function revenuePerProduct(?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        return Payment::query()
            ->select('product_id', DB::raw('SUM(amount) as revenue'))
            ->where('status', 'success')
            ->when($from, fn ($query) => $query->where('paid_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('paid_at', '<=', $to))
            ->groupBy('product_id')
            ->with('product:id,name,product_code')
            ->get();
    }

    public function revenueTimeline(string $period = 'daily', ?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        $dateExpression = match ($period) {
            'weekly' => DB::raw('DATE_SUB(DATE(paid_at), INTERVAL WEEKDAY(paid_at) DAY)'),
            'monthly' => DB::raw('DATE_FORMAT(paid_at, "%Y-%m-01")'),
            default => DB::raw('DATE(paid_at)'),
        };

        return Payment::query()
            ->selectRaw("{$dateExpression} as period_start, SUM(amount) as revenue")
            ->where('status', 'success')
            ->when($from, fn ($query) => $query->where('paid_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('paid_at', '<=', $to))
            ->groupBy('period_start')
            ->orderBy('period_start')
            ->get();
    }

    public function resolveRange(?string $from, ?string $to): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate = $to ? Carbon::parse($to)->endOfDay() : null;

        return [$fromDate, $toDate];
    }
}
