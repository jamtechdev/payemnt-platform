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
        return Inertia::render('Admin/CustomerService/Dashboard', [
            'totalCustomers' => Customer::query()->count(),
            'activeCustomers' => Customer::query()->active()->count(),
            'expiringSoon' => Customer::query()->expiringSoon()->with(['partner', 'product'])->get(),
            'newThisWeek' => Customer::query()->whereDate('created_at', '>=', now()->subDays(7))->count(),
        ]);
    }

    public function reconciliationDashboard(): Response
    {
        return Inertia::render('Admin/Reconciliation/Dashboard', [
            'monthlyCustomers' => Customer::query()->whereMonth('created_at', now()->month)->count(),
            'monthlyRevenue' => Payment::query()->whereMonth('payment_date', now()->month)->sum('amount'),
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
