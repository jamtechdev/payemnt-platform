<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ExportReportRequest;
use App\Jobs\GenerateReportExportJob;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Partner;
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
        $period = $request->string('period', 'monthly')->toString();
        $format = match ($period) {
            'daily' => '%Y-%m-%d',
            'yearly' => '%Y',
            default => '%Y-%m',
        };

        $query = Payment::query()
            ->selectRaw("product_id, partner_id, DATE_FORMAT(created_at, '{$format}') as bucket, count(*) as total")
            ->groupBy('product_id', 'partner_id', 'bucket')
            ->orderBy('bucket');

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
            'partners' => Partner::query()->select(['id', 'name'])->orderBy('name')->get(),
            'products' => Product::query()->select(['id', 'name'])->orderBy('name')->get(),
            'filters' => $request->only(['period', 'date_from', 'date_to', 'partner_id', 'product_id']),
        ]);
    }

    public function revenueByProduct(Request $request): Response
    {
        if (! $request->user()->can('reports.revenue_by_product')) {
            return redirect()->route('admin.reports.dashboard')->with('error', 'Access denied.');
        }

        $period = $request->string('period', 'monthly')->toString();
        $format = match ($period) {
            'daily' => '%Y-%m-%d',
            'yearly' => '%Y',
            default => '%Y-%m',
        };

        $query = Payment::query()
            ->join('products', 'payments.product_id', '=', 'products.id')
            ->join('partners', 'payments.partner_id', '=', 'partners.id')
            ->selectRaw("payments.product_id, products.name as product_name, payments.partner_id, partners.name as partner_name, DATE_FORMAT(payments.created_at, '{$format}') as bucket, COUNT(payments.id) as customer_count, COALESCE(products.guide_price, products.price, 0) as guide_price, (COUNT(payments.id) * COALESCE(products.guide_price, products.price, 0)) as expected_revenue")
            ->groupBy('payments.product_id', 'products.name', 'payments.partner_id', 'partners.name', 'bucket', 'products.guide_price', 'products.price');

        if ($request->filled('date_from')) {
            $query->whereDate('payments.created_at', '>=', $request->string('date_from')->toString());
        }
        if ($request->filled('date_to')) {
            $query->whereDate('payments.created_at', '<=', $request->string('date_to')->toString());
        }
        if ($request->filled('partner_id')) {
            $query->where('payments.partner_id', $request->integer('partner_id'));
        }
        if ($request->filled('product_id')) {
            $query->where('payments.product_id', $request->integer('product_id'));
        }

        $rows = $query->orderBy('bucket')->get();

        return Inertia::render('Admin/Reconciliation/RevenueByProductReport', [
            'rows' => $rows,
            'partners' => Partner::query()->select(['id', 'name'])->orderBy('name')->get(),
            'products' => Product::query()->select(['id', 'name'])->orderBy('name')->get(),
            'filters' => $request->only(['period', 'date_from', 'date_to', 'partner_id', 'product_id']),
        ]);
    }

    public function partnerPerformance(Request $request): Response
    {
        $months = max(3, min(24, $request->integer('months', 12)));

        $customerRows = Payment::query()
            ->selectRaw("partner_id, DATE_FORMAT(created_at, '%Y-%m') as bucket, count(*) as customer_count")
            ->where('created_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->groupBy('partner_id', 'bucket')
            ->get();

        $revenueRows = Payment::query()
            ->join('products', 'payments.product_id', '=', 'products.id')
            ->selectRaw("payments.partner_id, DATE_FORMAT(payments.created_at, '%Y-%m') as bucket, COALESCE(sum(COALESCE(products.guide_price, products.price, 0)),0) as total_revenue")
            ->where('payments.created_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->groupBy('partner_id', 'bucket')
            ->get();

        $partnerNames = Partner::query()->pluck('name', 'id');

        $pointsByPartner = [];
        foreach ($customerRows as $row) {
            $partnerId = (int) $row->partner_id;
            $pointsByPartner[$partnerId][$row->bucket]['bucket'] = $row->bucket;
            $pointsByPartner[$partnerId][$row->bucket]['customer_count'] = (int) $row->customer_count;
        }
        foreach ($revenueRows as $row) {
            $partnerId = (int) $row->partner_id;
            $pointsByPartner[$partnerId][$row->bucket]['bucket'] = $row->bucket;
            $pointsByPartner[$partnerId][$row->bucket]['total_revenue'] = (float) $row->total_revenue;
        }

        $series = collect($pointsByPartner)
            ->map(function (array $points, int $partnerId) use ($partnerNames): array {
                ksort($points);
                $normalized = collect($points)->map(function (array $point): array {
                    return [
                        'bucket' => $point['bucket'],
                        'customer_count' => (int) ($point['customer_count'] ?? 0),
                        'total_revenue' => (float) ($point['total_revenue'] ?? 0),
                    ];
                })->values()->all();

                $first = $normalized[0]['customer_count'] ?? 0;
                $last = $normalized[count($normalized) - 1]['customer_count'] ?? 0;
                $growth = $last - $first;

                return [
                    'partner_id' => $partnerId,
                    'partner_name' => $partnerNames[$partnerId] ?? 'Partner #'.$partnerId,
                    'growth_delta' => $growth,
                    'trend' => $growth >= 0 ? 'growth' : 'loss',
                    'points' => $normalized,
                ];
            })
            ->values();

        return Inertia::render('Admin/SuperAdmin/PartnerPerformance', [
            'months' => $months,
            'series' => $series,
        ]);
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
