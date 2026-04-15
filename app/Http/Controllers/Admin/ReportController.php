<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ExportReportRequest;
use App\Jobs\GenerateReportExportJob;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function customerAcquisition(Request $request): Response
    {
        $period = $request->string('period', 'monthly')->toString();
        $format = match ($period) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%x-%v',
            default => '%Y-%m',
        };

        return Inertia::render('Admin/Reconciliation/CustomerAcquisitionReport', [
            'rows' => Customer::query()
                ->selectRaw("product_id, DATE_FORMAT(created_at, '{$format}') as bucket, count(*) as total")
                ->groupBy('product_id', 'bucket')
                ->orderBy('bucket')
                ->get(),
            'filters' => $request->all(),
        ]);
    }

    public function revenueByProduct(Request $request): Response
    {
        if (! $request->user()->can('reports.revenue_by_product')) {
            return redirect()->route('admin.reports.dashboard')->with('error', 'Access denied.');
        }

        return Inertia::render('Admin/Reconciliation/RevenueByProductReport', [
            'rows' => Payment::query()->selectRaw('users.product_id, sum(amount) as total')
                ->join('users', 'users.id', '=', 'payments.customer_id')
                ->groupBy('users.product_id')->get(),
        ]);
    }

    public function partnerPerformance(): Response
    {
        return Inertia::render('Admin/SuperAdmin/PartnerPerformance', []);
    }

    public function exportReport(ExportReportRequest $request): JsonResponse
    {
        $jobId = (string) Str::uuid();
        Cache::put("report_export_job:{$jobId}", ['status' => 'queued'], now()->addHour());
        GenerateReportExportJob::dispatch($jobId, $request->validated());

        return response()->json(['job_id' => $jobId, 'format' => $request->input('format'), 'status' => 'queued']);
    }

    public function downloadExport(string $jobId)
    {
        $state = Cache::get("report_export_job:{$jobId}");
        if (! $state) {
            return response()->json(['status' => 'not_found'], 404);
        }
        if (($state['status'] ?? null) !== 'completed') {
            return response()->json(['status' => $state['status'] ?? 'processing']);
        }

        return Storage::disk('local')->download($state['path']);
    }
}
