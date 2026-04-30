<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Exports\AnalyticsExport;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Jobs\GenerateReportExportJob;
use App\Services\InsurtechAnalyticsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminAnalyticsController extends BaseApiController
{
    public function __construct(private readonly InsurtechAnalyticsService $analyticsService)
    {
    }

    public function summary(Request $request): JsonResponse
    {
        [$fromDate, $toDate] = $this->analyticsService->resolveRange(
            $request->string('from')->value(),
            $request->string('to')->value()
        );
        $period = $request->string('period', 'daily')->value();

        return $this->success([
            'customers_per_partner' => $this->analyticsService->customersPerPartner($fromDate, $toDate),
            'estimated_revenue' => [
                'daily' => $this->analyticsService->estimatedRevenueByPeriod('daily', $fromDate, $toDate),
                'monthly' => $this->analyticsService->estimatedRevenueByPeriod('monthly', $fromDate, $toDate),
                'yearly' => $this->analyticsService->estimatedRevenueByPeriod('yearly', $fromDate, $toDate),
            ],
            'partner_performance' => $this->analyticsService->partnerPerformanceMonthly($fromDate, $toDate),
            'selected_period_revenue' => $this->analyticsService->estimatedRevenueByPeriod($period, $fromDate, $toDate),
        ]);
    }

    public function export(Request $request): JsonResponse|BinaryFileResponse
    {
        $format = $request->string('format', 'csv')->lower()->value();
        $async = $request->boolean('async', false);

        if ($async) {
            $jobId = (string) Str::uuid();
            GenerateReportExportJob::dispatch($jobId, [
                'format' => $format,
                'period' => $request->string('period', 'daily')->value(),
                'from' => $request->string('from')->value(),
                'to' => $request->string('to')->value(),
            ]);

            return $this->success(['job_id' => $jobId, 'status' => 'queued'], 202);
        }

        $data = $this->summary($request)->getData(true)['data'];

        if ($format === 'excel') {
            return Excel::download(new AnalyticsExport($data), 'analytics-report.xlsx');
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.analytics', ['report' => $data]);
            $path = storage_path('app/public/analytics-report.pdf');
            $pdf->save($path);

            return response()->download($path)->deleteFileAfterSend(true);
        }

        return Excel::download(new AnalyticsExport($data), 'analytics-report.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    public function exportStatus(string $jobId): JsonResponse
    {
        $status = Cache::get("report_export_job:{$jobId}", ['status' => 'not_found']);
        return $this->success($status);
    }

    public function exportDownload(string $jobId): BinaryFileResponse
    {
        $status = Cache::get("report_export_job:{$jobId}");
        abort_if(! $status || ($status['status'] ?? null) !== 'completed', 404, 'Export not ready.');
        abort_unless(Storage::disk('local')->exists($status['path']), 404, 'Export file missing.');

        return Storage::disk('local')->download($status['path']);
    }
}
