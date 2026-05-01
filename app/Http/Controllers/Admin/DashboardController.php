<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ApiLog;
use App\Models\Customer;
use App\Models\FundWallet;
use App\Models\Occupation;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\ProductsPurchase;
use App\Models\ProductsPurchasesClaim;
use App\Models\ReferralUsage;
use App\Models\Relationship;
use App\Models\SwapOffer;
use App\Models\TaskType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function customerServiceDashboard(): Response
    {
        // BRD CS-001: Dashboard showing customer count per partner
        $partnerIds = Customer::query()->distinct()->pluck('partner_id')->filter();
        $partnerNames = Partner::query()->whereIn('id', $partnerIds)->pluck('name', 'id');

        $customersByPartner = Customer::query()
            ->selectRaw('partner_id, count(*) as total')
            ->groupBy('partner_id')
            ->get()
            ->map(fn ($row) => [
                'partner_id' => $row->partner_id,
                'partner_name' => $partnerNames[$row->partner_id] ?? 'Unknown',
                'total' => (int) $row->total,
            ]);

        // BRD open question 4: flag expiring covers
        $expiringSoon = Customer::query()
            ->expiringSoon()
            ->with(['partner:id,name', 'product:id,name'])
            ->get()
            ->map(fn (Customer $c) => [
                'uuid' => $c->uuid,
                'full_name' => $c->full_name,
                'email' => $c->email,
                'cover_end_date' => optional($c->cover_end_date)->toDateString(),
                'partner_name' => $c->partner?->name,
                'product_name' => $c->product?->name,
            ]);

        return Inertia::render('Admin/CustomerService/Dashboard', [
            'totalCustomers' => Customer::query()->count(),
            'activeCustomers' => Customer::query()->active()->count(),
            'newThisWeek' => Customer::query()->whereDate('created_at', '>=', now()->subDays(7))->count(),
            'customersByPartner' => $customersByPartner,
            'expiringSoon' => $expiringSoon,
            'expiringSoonCount' => $expiringSoon->count(),
        ]);
    }

    public function reconciliationDashboard(): Response
    {
        // BRD REC-001: Customer count per product — resolve product names separately
        $productIds = Customer::query()->distinct()->pluck('product_id')->filter();
        $productNames = \App\Models\Product::query()->whereIn('id', $productIds)->pluck('name', 'id');

        $customersByProduct = Customer::query()
            ->selectRaw('product_id, count(*) as total')
            ->groupBy('product_id')
            ->get()
            ->map(fn ($row) => [
                'product_id' => $row->product_id,
                'product_name' => $productNames[$row->product_id] ?? 'Unknown',
                'total' => (int) $row->total,
            ]);

        // BRD REC-003: Income per product line
        $revenueByProduct = Payment::query()
            ->selectRaw('payments.product_id, COUNT(payments.id) as transactions_count, SUM(COALESCE(products.guide_price, products.price, 0)) as total_revenue')
            ->join('products', 'products.id', '=', 'payments.product_id')
            ->whereMonth('payments.created_at', now()->month)
            ->groupBy('payments.product_id')
            ->get();

        return Inertia::render('Admin/Reconciliation/Dashboard', [
            'monthlyCustomers' => Customer::query()->whereMonth('created_at', now()->month)->count(),
            'monthlyRevenue' => (float) Payment::query()
                ->join('products', 'products.id', '=', 'payments.product_id')
                ->whereMonth('payments.created_at', now()->month)
                ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(products.guide_price, products.price, 0)')),
            'customersByProduct' => $customersByProduct,
            'revenueByProduct' => $revenueByProduct,
        ]);
    }

    public function superAdminDashboard(): Response
    {
        $monthlyPayments = Payment::query()
            ->selectRaw("DATE_FORMAT(paid_at, '%b %e') as label, SUM(COALESCE(products.guide_price, products.price, 0)) as total")
            ->join('products', 'products.id', '=', 'payments.product_id')
            ->whereDate('paid_at', '>=', now()->subDays(30))
            ->groupBy('label')
            ->orderByRaw('MIN(paid_at)')
            ->get();

        $totalRevenue = Payment::query()
            ->join('products', 'products.id', '=', 'payments.product_id')
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(products.guide_price, products.price, 0)'));

        $monthlyRevenue = Payment::query()
            ->join('products', 'products.id', '=', 'payments.product_id')
            ->whereMonth('payments.paid_at', now()->month)
            ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(products.guide_price, products.price, 0)'));

        $dbHealth = [
            'users' => \App\Models\User::query()->count(),
            'partners' => Partner::query()->count(),
            'products' => \App\Models\Product::query()->count(),
            'customers' => Customer::query()->count(),
            'payments' => Payment::query()->count(),
        ];
        $apiHealth = [
            'requests_24h' => ApiLog::query()->where('requested_at', '>=', now()->subDay())->count(),
            'failed_24h' => ApiLog::query()->where('requested_at', '>=', now()->subDay())->where('status_code', '>=', 400)->count(),
            'avg_latency_ms_24h' => (int) round(ApiLog::query()->where('requested_at', '>=', now()->subDay())->avg('response_time_ms') ?? 0),
        ];

        return Inertia::render('Admin/SuperAdmin/PlatformDashboard', [
            'totalCustomers' => Customer::query()->count(),
            'totalPartners' => Partner::query()->count(),
            'allRevenue'    => (float) $totalRevenue,
            'monthlyRevenue'=> (float) $monthlyRevenue,
            'recentAuditLogs' => AuditLog::query()->latest('created_at')->limit(10)->get(),
            'activeUsers' => \App\Models\User::query()->where('is_active', true)->count(),
            'activeProducts' => \App\Models\Product::query()->where('status', 'active')->count(),
            'inactiveProducts' => \App\Models\Product::query()->where('status', 'inactive')->count(),
            'coveredCustomers' => Customer::query()->where('status', 'active')->count(),
            'notCoveredCustomers' => Customer::query()->where('status', '!=', 'active')->count(),
            'monthlyPayments' => $monthlyPayments,
            'dbHealth'        => $dbHealth,
            'apiHealth' => $apiHealth,
            'stats' => [
                'transactions'      => \App\Models\Payment::query()->count(),
                'swap_offers'       => SwapOffer::query()->count(),
                'occupations'       => Occupation::query()->count(),
                'relationships'     => Relationship::query()->count(),
                'task_types'        => TaskType::query()->count(),
                'referral_usages'   => ReferralUsage::query()->count(),
                'purchases'         => ProductsPurchase::query()->count(),
                'purchase_claims'   => ProductsPurchasesClaim::query()->count(),
                'fund_wallets'      => FundWallet::query()->count(),
            ],
        ]);
    }
}
