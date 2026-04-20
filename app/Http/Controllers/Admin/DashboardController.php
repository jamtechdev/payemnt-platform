<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Payment;
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
            ->selectRaw('users.product_id, SUM(payments.amount) as total_revenue')
            ->join('users', 'users.id', '=', 'payments.customer_id')
            ->whereMonth('payments.payment_date', now()->month)
            ->groupBy('users.product_id')
            ->get();

        return Inertia::render('Admin/Reconciliation/Dashboard', [
            'monthlyCustomers' => Customer::query()->whereMonth('created_at', now()->month)->count(),
            'monthlyRevenue' => (float) Payment::query()->whereMonth('payment_date', now()->month)->sum('amount'),
            'customersByProduct' => $customersByProduct,
            'revenueByProduct' => $revenueByProduct,
        ]);
    }

    public function superAdminDashboard(): Response
    {
        $monthlyPayments = Payment::query()
            ->selectRaw("DATE_FORMAT(payment_date, '%b %e') as label, SUM(amount) as total")
            ->whereDate('payment_date', '>=', now()->subDays(30))
            ->groupBy('label')
            ->orderByRaw('MIN(payment_date)')
            ->get();

        $dbHealth = [
            'users' => \App\Models\User::query()->count(),
            'partners' => Partner::query()->count(),
            'products' => \App\Models\Product::query()->count(),
            'customers' => Customer::query()->count(),
            'payments' => Payment::query()->count(),
        ];

        return Inertia::render('Admin/SuperAdmin/PlatformDashboard', [
            'totalCustomers' => Customer::query()->count(),
            'totalPartners' => Partner::query()->count(),
            'allRevenue' => Payment::query()->sum('amount'),
            'recentAuditLogs' => AuditLog::query()->latest('created_at')->limit(10)->get(),
            'activeUsers' => \App\Models\User::query()->where('is_active', true)->count(),
            'activeProducts' => \App\Models\Product::query()->where('status', 'active')->count(),
            'inactiveProducts' => \App\Models\Product::query()->where('status', 'inactive')->count(),
            'coveredCustomers' => Customer::query()->where('status', 'active')->count(),
            'notCoveredCustomers' => Customer::query()->where('status', '!=', 'active')->count(),
            'monthlyPayments' => $monthlyPayments,
            'dbHealth' => $dbHealth,
        ]);
    }
}
