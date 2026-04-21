<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exports\AnalyticsExport;
use App\Services\ReportingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class GenerateReportExportJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;
    public int $tries = 3;

    public function __construct(public string $jobId, public array $filters = []) {}

    public function handle(): void
    {
        Cache::put("report_export_job:{$this->jobId}", ['status' => 'processing'], now()->addHour());

        $reportingService = app(ReportingService::class);
        [$fromDate, $toDate] = $reportingService->resolveRange(
            $this->filters['from'] ?? null,
            $this->filters['to'] ?? null
        );

        $period = $this->filters['period'] ?? 'daily';
        $payload = [
            'customers_per_partner' => $reportingService->customerCountPerPartner($fromDate, $toDate)->toArray(),
            'customers_per_product' => $reportingService->customerCountPerProduct($fromDate, $toDate)->toArray(),
            'revenue_per_product' => $reportingService->revenuePerProduct($fromDate, $toDate)->toArray(),
            'revenue_timeline' => $reportingService->revenueTimeline($period, $fromDate, $toDate)->toArray(),
        ];

        $format = $this->filters['format'] ?? 'csv';
        $extension = $format === 'excel' ? 'xlsx' : 'csv';
        $path = "exports/report-{$this->jobId}.{$extension}";
        Storage::disk('local')->makeDirectory('exports');

        if ($format === 'excel') {
            Excel::store(new AnalyticsExport($payload), $path, 'local');
        } else {
            Excel::store(new AnalyticsExport($payload), $path, 'local', \Maatwebsite\Excel\Excel::CSV);
        }

        Cache::put("report_export_job:{$this->jobId}", ['status' => 'completed', 'path' => $path], now()->addHour());
    }
}
