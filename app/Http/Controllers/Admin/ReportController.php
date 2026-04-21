<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ExportReportRequest;
use App\Jobs\GenerateReportExportJob;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;
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
        // BRD REC-004: Time period filtering
        $period = $request->string('period', 'monthly')->toString();
        $format = match ($period) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%x-%v',
            default => '%Y-%m',
        };

        $query = Customer::query()
            ->selectRaw("product_id, partner_id, DATE_FORMAT(created_at, '{$format}') as bucket, count(*) as total")
            ->groupBy('product_id', 'partner_id', 'bucket')
            ->orderBy('bucket');

        // BRD REC-004: Custom date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from')->toString());
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to')->toString());
        }
        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->integer('partner_id'));
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        $rows = $query->get();

        // Resolve names separately to avoid with() on grouped selectRaw
        $partnerNames = Partner::query()->pluck('name', 'id');
        $productNames = Product::query()->pluck('name', 'id');

        $rows = $rows->map(fn ($row) => [
            'product_id' => $row->product_id,
            'product_name' => $productNames[$row->product_id] ?? 'Product #'.$row->product_id,
            'partner_id' => $row->partner_id,
            'partner_name' => $partnerNames[$row->partner_id] ?? 'Partner #'.$row->partner_id,
            'bucket' => $row->bucket,
            'total' => (int) $row->total,
        ]);

        return Inertia::render('Admin/Reconciliation/CustomerAcquisitionReport', [
            'rows' => $rows,
            'filters' => $request->only(['period', 'date_from', 'date_to', 'partner_id', 'product_id']),
        ]);
    }

    public function revenueByProduct(Request $request): Response
    {
        if (! $request->user()->can('reports.revenue_by_product')) {
            return redirect()->route('admin.reports.dashboard')->with('error', 'Access denied.');
        }

        // BRD REC-004: Time period filtering for revenue
        $query = Payment::query()
            ->selectRaw('payments.product_id, SUM(payments.amount) as total_revenue, COUNT(payments.id) as payment_count')
            ->where('payments.status', 'success');

        if ($request->filled('date_from')) {
            $query->whereDate('payments.paid_at', '>=', $request->string('date_from')->toString());
        }
        if ($request->filled('date_to')) {
            $query->whereDate('payments.paid_at', '<=', $request->string('date_to')->toString());
        }
        if ($request->filled('period') && ! $request->filled('date_from')) {
            $period = $request->string('period')->toString();
            $query->when($period === 'daily', fn ($q) => $q->whereDate('payments.paid_at', today()))
                ->when($period === 'weekly', fn ($q) => $q->whereBetween('payments.paid_at', [now()->startOfWeek(), now()->endOfWeek()]))
                ->when($period === 'monthly', fn ($q) => $q->whereMonth('payments.paid_at', now()->month)->whereYear('payments.paid_at', now()->year));
        }

        $rows = $query->groupBy('payments.product_id')->get();

        return Inertia::render('Admin/Reconciliation/RevenueByProductReport', [
            'rows' => $rows,
            'filters' => $request->only(['period', 'date_from', 'date_to']),
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
