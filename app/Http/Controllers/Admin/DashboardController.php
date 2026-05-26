<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ApiLog;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\FundWallet;
use App\Models\Occupation;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductsPurchase;
use App\Models\ProductsPurchasesClaim;
use App\Models\ReferralUsage;
use App\Models\Relationship;
use App\Models\SwapOffer;
use App\Models\TaskType;
use App\Models\User;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private readonly CurrencyService $currencyService)
    {
    }

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

        $recentActivity = AuditLog::query()
            ->whereIn('entity_type', [Customer::class, Payment::class])
            ->with(['actor:id,name'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'actor_name' => $log->actor?->name ?? 'System',
                'entity_type' => class_basename($log->entity_type),
                'created_at' => $log->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Admin/CustomerService/Dashboard', [
            'totalCustomers' => Customer::query()->count(),
            'activeCustomers' => Customer::query()->active()->count(),
            'newThisWeek' => Customer::query()->whereDate('created_at', '>=', now()->subDays(7))->count(),
            'customersByPartner' => $customersByPartner,
            'expiringSoon' => $expiringSoon,
            'expiringSoonCount' => $expiringSoon->count(),
            'recentActivity' => $recentActivity,
        ]);
    }

    public function reconciliationDashboard(): Response
    {
        $user = request()->user();
        $preferredCurrency = $user?->preferred_currency ?? 'USD';

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

        // BRD REC-003: Income per product line (Currency Aware)
        $revenueRows = Payment::query()
            ->selectRaw('payments.product_id, payments.currency, COUNT(payments.id) as transactions_count, SUM(payments.amount) as total_revenue')
            ->where('payments.status', 'success')
            ->whereMonth('payments.paid_at', now()->month)
            ->groupBy('payments.product_id', 'payments.currency')
            ->get();

        $revenueByProduct = $revenueRows->groupBy('product_id')->map(function ($rows, $productId) use ($preferredCurrency, $productNames) {
            $totalInPreferred = $rows->reduce(function ($carry, $row) use ($preferredCurrency) {
                return $carry + $this->currencyService->convert((float) $row->total_revenue, $row->currency, $preferredCurrency);
            }, 0.0);

            return [
                'product_id' => $productId,
                'product_name' => $productNames[$productId] ?? 'Unknown',
                'total_revenue' => $totalInPreferred,
                'breakdown' => $rows->map(fn ($r) => ['currency' => $r->currency, 'amount' => (float) $r->total_revenue]),
            ];
        })->values();

        $monthlyRevenueByCurrency = Payment::query()
            ->selectRaw('currency, SUM(amount) as total')
            ->where('status', 'success')
            ->whereMonth('paid_at', now()->month)
            ->groupBy('currency')
            ->get();

        $monthlyRevenue = $monthlyRevenueByCurrency->reduce(function ($carry, $row) use ($preferredCurrency) {
            return $carry + $this->currencyService->convert((float) $row->total, $row->currency, $preferredCurrency);
        }, 0.0);

        $recentPayments = Payment::query()
            ->where('status', 'success')
            ->with(['partner:id,name', 'product:id,name'])
            ->latest('paid_at')
            ->limit(10)
            ->get()
            ->map(fn (Payment $p) => [
                'id' => $p->id,
                'transaction_number' => $p->transaction_number,
                'amount' => (float) $p->amount,
                'currency' => $p->currency,
                'paid_at' => $p->paid_at?->toDateTimeString(),
                'partner_name' => $p->partner?->name,
                'product_name' => $p->product?->name,
                'customer_name' => $p->customer_name,
            ]);

        $revenueTrendRaw = Payment::query()
            ->selectRaw("DATE_FORMAT(paid_at, '%b %Y') as month, currency, SUM(amount) as total")
            ->where('status', 'success')
            ->where('paid_at', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('month', 'currency')
            ->orderByRaw('MIN(paid_at)')
            ->get();

        $revenueTrend = $revenueTrendRaw->groupBy('month')->map(function ($rows, $month) use ($preferredCurrency) {
            $totalInPreferred = $rows->reduce(function ($carry, $row) use ($preferredCurrency) {
                return $carry + $this->currencyService->convert((float) $row->total, $row->currency, $preferredCurrency);
            }, 0.0);

            return [
                'label' => $month,
                'total' => $totalInPreferred,
            ];
        })->values();

        return Inertia::render('Admin/Reconciliation/Dashboard', [
            'monthlyCustomers' => Customer::query()->whereMonth('created_at', now()->month)->count(),
            'monthlyRevenue' => (float) $monthlyRevenue,
            'preferredCurrency' => $preferredCurrency,
            'customersByProduct' => $customersByProduct,
            'revenueByProduct' => $revenueByProduct,
            'revenueBreakdown' => $monthlyRevenueByCurrency,
            'recentPayments' => $recentPayments,
            'revenueTrend' => $revenueTrend,
        ]);
    }

    public function superAdminDashboard(): Response
    {
        $user = request()->user();
        $preferredCurrency = $user?->preferred_currency ?? 'USD';

        $monthlyPaymentsRaw = Payment::query()
            ->selectRaw("DATE_FORMAT(paid_at, '%b %e') as label, currency, SUM(amount) as total")
            ->where('status', 'success')
            ->whereDate('paid_at', '>=', now()->subDays(30))
            ->groupBy('label', 'currency')
            ->orderByRaw('MIN(paid_at)')
            ->get();

        $monthlyPayments = $monthlyPaymentsRaw->groupBy('label')->map(function ($rows, $label) use ($preferredCurrency) {
            $totalInPreferred = $rows->reduce(function ($carry, $row) use ($preferredCurrency) {
                return $carry + $this->currencyService->convert((float) $row->total, $row->currency, $preferredCurrency);
            }, 0.0);

            return [
                'label' => $label,
                'total' => $totalInPreferred,
            ];
        })->values();

        $allRevenueByCurrency = Payment::query()
            ->selectRaw('currency, SUM(amount) as total')
            ->where('status', 'success')
            ->groupBy('currency')
            ->get();

        $totalRevenue = $allRevenueByCurrency->reduce(function ($carry, $row) use ($preferredCurrency) {
            return $carry + $this->currencyService->convert((float) $row->total, $row->currency, $preferredCurrency);
        }, 0.0);

        $monthlyRevenueByCurrency = Payment::query()
            ->selectRaw('currency, SUM(amount) as total')
            ->where('status', 'success')
            ->whereMonth('paid_at', now()->month)
            ->groupBy('currency')
            ->get();

        $monthlyRevenue = $monthlyRevenueByCurrency->reduce(function ($carry, $row) use ($preferredCurrency) {
            return $carry + $this->currencyService->convert((float) $row->total, $row->currency, $preferredCurrency);
        }, 0.0);

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
            'preferredCurrency' => $preferredCurrency,
            'revenueBreakdown' => $allRevenueByCurrency,
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

    public function partnerDashboard(): Response
    {
        $user = request()->user();
        $partner = Partner::query()->where('contact_email', $user->email)->first();

        if (! $partner) {
            return Inertia::render('Admin/Partner/Dashboard', [
                'partner' => null,
                'stats' => [],
                'recentTransactions' => [],
                'products' => [],
                'currency' => 'USD',
            ]);
        }

        $recentTransactions = Payment::query()
            ->where('partner_id', $partner->id)
            ->with('product:id,name,product_code')
            ->latest()
            ->limit(10)
            ->get();

        $products = $partner->products()
            ->wherePivot('is_enabled', true)
            ->get(['products.id', 'products.name', 'products.product_code']);

        $currency = $user->preferred_currency ?? 'USD';

        return Inertia::render('Admin/Partner/Dashboard', [
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'partner_code' => $partner->partner_code,
                'contact_email' => $partner->contact_email,
                'status' => $partner->status,
                'connected_at' => $partner->connected_at?->format('d M Y'),
                'has_api_key' => $partner->hasActiveApiKey(),
            ],
            'stats' => [
                'total_customers' => $partner->customers()->count(),
                'active_customers' => $partner->customers()->whereHas('payments', fn ($q) => $q->where('status', 'success'))->count(),
                'total_transactions' => $partner->payments()->count(),
                'monthly_transactions' => $partner->payments()->whereMonth('created_at', now()->month)->count(),
            ],
            'recentTransactions' => $recentTransactions->map(fn ($t) => [
                'id' => $t->id,
                'transaction_number' => $t->transaction_number,
                'customer_name' => $t->customer_name,
                'product_name' => $t->product?->name,
                'amount' => (float) $t->amount,
                'currency' => $t->currency,
                'status' => $t->status,
                'created_at' => $t->created_at->format('d M Y'),
            ]),
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'product_code' => $p->product_code,
            ]),
            'currency' => $currency,
        ]);
    }

    public function partnerProducts(): Response
    {
        $user = request()->user();
        $partner = Partner::query()->where('contact_email', $user->email)->firstOrFail();

        $products = $partner->products()
            ->withPivot(['is_enabled', 'currency_id', 'base_price', 'guide_price', 'cover_duration_days_override', 'rule_overrides'])
            ->get(['products.id', 'products.name', 'products.product_code', 'products.description', 'products.status', 'products.category']);

        return Inertia::render('Admin/Partner/Products', [
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'partner_code' => $partner->partner_code,
            ],
            'products' => $products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'product_code' => $p->product_code,
                'description' => $p->description,
                'status' => $p->status,
                'category' => $p->category,
                'base_price' => (float) ($p->pivot->base_price ?? 0),
                'guide_price' => (float) ($p->pivot->guide_price ?? 0),
                'cover_duration_days_override' => $p->pivot->cover_duration_days_override,
                'is_enabled' => $p->pivot->is_enabled,
            ]),
        ]);
    }

    public function partnerProfile(): Response
    {
        $user = request()->user();
        $partner = Partner::query()->where('contact_email', $user->email)->firstOrFail();

        return Inertia::render('Admin/Partner/Profile', [
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'partner_code' => $partner->partner_code,
                'contact_email' => $partner->contact_email,
                'contact_phone' => $partner->contact_phone,
                'company_name' => $partner->company_name,
                'website_url' => $partner->website_url,
                'connected_at' => $partner->connected_at?->format('d M Y'),
                'connected_base_url' => $partner->connected_base_url,
                'has_api_key' => $partner->hasActiveApiKey(),
            ],
        ]);
    }

    public function partnerUpdateProfile(Request $request): RedirectResponse
    {
        $user = request()->user();
        $partner = Partner::query()->where('contact_email', $user->email)->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'contact_email' => 'sometimes|email|max:255|unique:partners,contact_email,'.$partner->id,
            'contact_phone' => 'nullable|string|max:50',
        ]);

        $partner->update($validated);

        // Update linked user email too
        if (isset($validated['contact_email']) && $user->email !== $validated['contact_email']) {
            $user->update(['email' => $validated['contact_email']]);
        }

        if (isset($validated['name'])) {
            $user->update(['name' => $validated['name']]);
        }

        return redirect()->back()->with('success', 'Profile updated.');
    }

    public function partnerAuditLogs(): Response
    {
        $user = request()->user();
        $partner = Partner::query()->where('contact_email', $user->email)->firstOrFail();

        $logs = AuditLog::query()
            ->where('partner_id', $partner->id)
            ->with('actor:id,name')
            ->latest('created_at')
            ->paginate(50);

        return Inertia::render('Admin/Partner/AuditLog', [
            'logs' => $logs,
        ]);
    }
}
