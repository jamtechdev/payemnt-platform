<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Payment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class GenerateReportExportJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;
    public int $tries = 3;

    public function __construct(public string $jobId, public array $filters = []) {}

    public function handle(): void
    {
        Cache::put("report_export_job:{$this->jobId}", ['status' => 'processing'], now()->addHour());

        $rows = Payment::query()
            ->selectRaw('users.product_id, sum(payments.amount) as total_amount, count(payments.id) as payment_count')
            ->join('users', 'users.id', '=', 'payments.customer_id')
            ->groupBy('users.product_id')
            ->get();

        $format = $this->filters['format'] ?? 'csv';
        $content = "product_id,total_amount,payment_count\n";
        foreach ($rows as $row) {
            $content .= "{$row->product_id},{$row->total_amount},{$row->payment_count}\n";
        }

        $extension = $format === 'excel' ? 'xlsx' : ($format === 'pdf' ? 'pdf' : 'csv');
        $path = "exports/report-{$this->jobId}.{$extension}";
        Storage::disk('local')->put($path, $content);

        Cache::put("report_export_job:{$this->jobId}", ['status' => 'completed', 'path' => $path], now()->addHour());
    }
}
