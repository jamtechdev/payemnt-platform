<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePartnerRequest;
use App\Http\Requests\Admin\UpdatePartnerRequest;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class PartnerController extends Controller
{
    public function index(): Response
    {
        $partners = Partner::query()
            ->withCount(['customers', 'products'])
            ->with('tokens')
            ->paginate(15);

        // Add API key status to each partner
        $partners->getCollection()->transform(function ($partner) {
            $partner->api_key_status = $partner->hasActiveApiKey() ? 'active' : 'inactive';
            return $partner;
        });

        return Inertia::render('Admin/SuperAdmin/PartnerList', [
            'partners' => $partners,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/SuperAdmin/PartnerCreate');
    }

    // public function store(StorePartnerRequest $request): RedirectResponse
    // {
    //     abort_unless($request->user()?->hasAnyRole(['admin', 'super_admin']), 403);

    //     Partner::query()->create([
    //         'name' => $request->string('name')->toString(),
    //         'slug' => Str::slug($request->string('name')->toString()),
    //         'email' => $request->string('email')->toString(),
    //         'phone' => $request->input('phone'),
    //         'status' => 'active',
    //     ])->syncRoles(['partner']);

    //     return redirect()->route('admin.partners.index')->with('success', 'Partner created.');
    // }
    public function store(StorePartnerRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        $partner = Partner::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'partner_code' => $request->filled('partner_code')
                ? strtoupper(Str::slug($request->partner_code, '_'))
                : strtoupper(Str::slug($request->name, '_') . '_' . strtoupper(Str::random(4))),
            'contact_email' => $request->email,
            'contact_phone' => $request->phone,
            'status' => 'active',
        ]);

        // Create partner profile if relation exists
        if (method_exists($partner, 'profile')) {
            $partner->profile()->firstOrCreate([
                'partner_id' => $partner->id,
            ], [
                'company_name' => $request->name,
                'contact_person' => $request->name,
                'billing_email' => $request->email,
            ]);
        }

        return redirect()
            ->route('admin.partners.index')
            ->with('success', 'Partner created successfully.');
    }

    public function show(Partner $partner): Response
    {
        $viewer = request()->user();
        $canViewPartnerPricing = (bool) $viewer?->hasRole('super_admin');
        $partner->load([
            'products' => function ($query) {
                $query->withPivot(['is_enabled', 'partner_price', 'partner_currency', 'cover_duration_days_override', 'rule_overrides']);
            },
            'customers' => function ($query) {
                $query->with('product:id,name')->latest()->limit(5);
            },
            'payments' => function ($query) {
                $query->latest()->limit(5);
            }
        ]);
        if (! $canViewPartnerPricing) {
            $partner->products->each(function ($product): void {
                if ($product->pivot) {
                    $product->pivot->partner_price = null;
                    $product->pivot->partner_currency = null;
                }
            });
        }

        // Get API key status
        $apiKeyStatus = $partner->hasActiveApiKey() ? 'active' : 'inactive';
        
        // Get customer and revenue stats
        $stats = [
            'total_customers' => $partner->customers()->count(),
            'active_customers' => $partner->customers()->whereHas('payments', function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            })->count(),
            'total_revenue' => $partner->payments()->sum('amount'),
            'monthly_revenue' => $partner->payments()->where('created_at', '>=', now()->startOfMonth())->sum('amount'),
            'api_key_status' => $apiKeyStatus,
            'last_api_activity' => $partner->api_key_last_generated_at?->format('M j, Y g:i A') ?? 'Never'
        ];

        return Inertia::render('Admin/SuperAdmin/PartnerDetail', [
            'partner' => $partner,
            'canViewPartnerPricing' => $canViewPartnerPricing,
            'stats' => $stats
        ]);
    }

    public function edit(Partner $partner): Response
    {
        return Inertia::render('Admin/SuperAdmin/PartnerEdit', [
            'partner' => $partner,
        ]);
    }

    // public function update(UpdatePartnerRequest $request, Partner $partner): RedirectResponse
    // {
    //     abort_unless($request->user()?->hasAnyRole(['admin', 'super_admin']), 403);

    //     $partner->update($request->only(['name', 'email', 'phone', 'status']));

    //     return back()->with('success', 'Partner updated.');
    // }

    public function update(UpdatePartnerRequest $request, Partner $partner): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        $partner->update([
            'name'          => $request->name ?? $partner->name,
            'contact_email' => $request->contact_email ?? $partner->contact_email,
            'contact_phone' => $request->contact_phone ?? $partner->contact_phone,
            'status'        => $request->status ?? $partner->status,
        ]);

        return redirect()
            ->route('admin.partners.index')
            ->with('success', 'Partner updated successfully.');
    }

    public function toggleStatus(Partner $partner): RedirectResponse
    {
        abort_unless(request()->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        $partner->update(['status' => $partner->status === 'active' ? 'inactive' : 'active']);

        return back()->with('success', 'Status toggled.');
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        abort_unless(request()->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        if ($partner->customers()->exists()) {
            return back()->with('error', 'Cannot delete partner with customer records.');
        }

        $partner->delete();

        return redirect()->route('admin.partners.index')->with('success', 'Partner deleted.');
    }

    public function generateApiKey(Partner $partner): RedirectResponse
    {
        abort_unless(request()->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        $apiKey = $partner->generateApiKey();

        return back()->with([
            'success' => 'API key generated successfully.',
            'api_key' => $apiKey,
            'show_api_key_modal' => true
        ]);
    }

    public function revokeApiKey(Partner $partner): RedirectResponse
    {
        abort_unless(request()->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        $partner->tokens()->delete();

        return back()->with('success', 'API key revoked successfully.');
    }

    public function toggleProductAccess(Partner $partner, Request $request): RedirectResponse
    {
        abort_unless(request()->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        $productId = $request->input('product_id');
        $isEnabled = $request->boolean('is_enabled');

        $partner->products()->updateExistingPivot($productId, [
            'is_enabled' => $isEnabled,
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Product access updated successfully.');
    }

}
