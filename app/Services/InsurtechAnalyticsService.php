<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InsurtechAnalyticsService
{
    public function resolveRange(?string $from, ?string $to): array
    {
        $fromDate = $from ? Carbon::parse($from)->startOfDay() : null;
        $toDate = $to ? Carbon::parse($to)->endOfDay() : null;

        return [$fromDate, $toDate];
    }

    public function customersPerPartner(?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        return Payment::query()
            ->selectRaw('partner_id, COUNT(*) as transactions_count')
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->groupBy('partner_id')
            ->with('partner:id,name,partner_name,partner_code')
            ->get();
    }

    public function estimatedRevenueByPeriod(string $period = 'daily', ?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        $dateExpression = match ($period) {
            'monthly' => DB::raw('DATE_FORMAT(payments.created_at, "%Y-%m-01")'),
            'yearly' => DB::raw('DATE_FORMAT(payments.created_at, "%Y-01-01")'),
            default => DB::raw('DATE(payments.created_at)'),
        };

        return Payment::query()
            ->join('products', 'products.id', '=', 'payments.product_id')
            ->selectRaw("{$dateExpression} as period_start, COUNT(payments.id) as transactions_count, SUM(COALESCE(products.guide_price, products.price, 0)) as estimated_revenue")
            ->when($from, fn ($query) => $query->where('payments.created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('payments.created_at', '<=', $to))
            ->groupBy('period_start')
            ->orderBy('period_start')
            ->get();
    }

    public function partnerPerformanceMonthly(?CarbonInterface $from = null, ?CarbonInterface $to = null): Collection
    {
        return Payment::query()
            ->selectRaw('partner_id, DATE_FORMAT(created_at, "%Y-%m-01") as period_start, COUNT(*) as transactions_count')
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->groupBy('partner_id', 'period_start')
            ->with('partner:id,name,partner_name,partner_code')
            ->orderBy('period_start')
            ->get();
    }
}
