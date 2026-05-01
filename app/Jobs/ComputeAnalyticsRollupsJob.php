<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ComputeAnalyticsRollupsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(): void
    {
        $date = now()->toDateString();
        $rows = Payment::query()
            ->selectRaw('partner_id, product_id, COUNT(*) as transactions_total')
            ->selectRaw("SUM(CASE WHEN status IN ('active','success') THEN 1 ELSE 0 END) as transactions_success")
            ->selectRaw("SUM(CASE WHEN status IN ('failed','cancelled','suspended') THEN 1 ELSE 0 END) as transactions_failed")
            ->selectRaw('SUM(amount) as estimated_revenue')
            ->whereDate('created_at', $date)
            ->groupBy('partner_id', 'product_id')
            ->get();

        foreach ($rows as $row) {
            DB::table('analytics_daily_rollups')->updateOrInsert(
                [
                    'period_date' => $date,
                    'partner_id' => $row->partner_id,
                    'product_id' => $row->product_id,
                ],
                [
                    'transactions_total' => (int) $row->transactions_total,
                    'transactions_success' => (int) $row->transactions_success,
                    'transactions_failed' => (int) $row->transactions_failed,
                    'estimated_revenue' => (float) $row->estimated_revenue,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
