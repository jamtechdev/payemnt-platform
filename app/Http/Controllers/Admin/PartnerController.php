<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePartnerRequest;
use App\Http\Requests\Admin\UpdatePartnerRequest;
use App\Models\AuditLog;
use App\Models\Partner;
use App\Support\SortSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class PartnerController extends Controller
{
    public function index(Request $request): Response
    {
        $sortConfig = SortSanitizer::resolve(
            $request->string('sort', 'created_at')->toString(),
            $request->string('direction', 'desc')->toString(),
            ['created_at', 'name', 'partner_code', 'status'],
            'created_at'
        );

        $partners = Partner::query()
            ->withCount(['customers', 'products'])
            ->with('tokens')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($scoped) use ($search): void {
                    $scoped->where('name', 'like', "%{$search}%")
                        ->orWhere('partner_code', 'like', "%{$search}%")
                        ->orWhere('contact_email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->orderBy($sortConfig['column'], $sortConfig['direction'])
            ->paginate(15);

        $partners->getCollection()->transform(function ($partner) {
            $partner->api_key_status = $partner->hasActiveApiKey() ? 'active' : 'inactive';
            $partner->connection_status = $partner->connected_at ? 'connected' : 'not_connected';
            return $partner;
        });

        $deletedPartners = Partner::onlyTrashed()
            ->withCount('customers')
            ->get()
            ->map(fn($p) => [
                'id'           => $p->id,
                'name'         => $p->name,
                'partner_code' => $p->partner_code,
                'contact_email'=> $p->contact_email,
                'deleted_at'   => $p->deleted_at?->format('d M Y'),
            ]);

        return Inertia::render('Admin/SuperAdmin/PartnerList', [
            'partners'        => $partners,
            'deletedPartners' => $deletedPartners,
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
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

        // Generate unique slug
        $baseSlug = Str::slug($request->name);
        $slug = $baseSlug;
        $i = 1;
        while (Partner::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $i++;
        }

        // Generate unique partner_code
        $baseCode = $request->filled('partner_code')
            ? strtoupper(Str::slug($request->partner_code, '_'))
            : strtoupper(Str::slug($request->name, '_'));
        $code = $baseCode;
        $j = 1;
        while (Partner::withTrashed()->where('partner_code', $code)->exists()) {
            $code = $baseCode . '_' . $j++;
        }

        $partner = Partner::create([
            'name'          => $request->name,
            'slug'          => $slug,
            'partner_code'  => $code,
            'contact_email' => $request->contact_email,
            'contact_phone' => $request->contact_phone,
            'company_name' => $request->input('company_name'),
            'website_url' => $request->input('website_url'),
            'notes' => $request->input('notes'),
            'created_by' => $request->user()?->id,
            'status'        => 'active',
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
        $partner->load([
            'customers' => function ($query) {
                $query->with('product:id,name')->latest()->limit(5);
            },
            'payments' => function ($query) {
                $query->latest()->limit(5);
            }
        ]);

        $apiKeyStatus = $partner->hasActiveApiKey() ? 'active' : 'inactive';
        $apiUsage = AuditLog::query()
            ->where('action', 'api_usage')
            ->where('partner_id', $partner->id);

        $stats = [
            'total_customers' => $partner->customers()->count(),
            'active_customers' => $partner->customers()->whereHas('payments', function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            })->count(),
            'total_revenue' => $partner->payments()
                ->join('products', 'payments.product_id', '=', 'products.id')
                ->sum('products.price'),
            'monthly_revenue' => $partner->payments()
                ->join('products', 'payments.product_id', '=', 'products.id')
                ->where('payments.created_at', '>=', now()->startOfMonth())
                ->sum('products.price'),
            'api_key_status' => $apiKeyStatus,
            'last_api_activity' => optional($apiUsage->latest('occurred_at')->first()?->occurred_at)?->format('M j, Y g:i A') ?? 'Never',
            'api_success_count' => (clone $apiUsage)->where('changes->outcome', 'success')->count(),
            'api_failure_count' => (clone $apiUsage)->where('changes->outcome', 'failure')->count(),
            'api_avg_latency_ms' => (int) round((clone $apiUsage)->avg('changes->duration_ms') ?? 0),
            'token_last_used_at' => optional($partner->tokens()->latest('last_used_at')->first()?->last_used_at)?->format('M j, Y g:i A') ?? 'Never',
        ];

        return Inertia::render('Admin/SuperAdmin/PartnerDetail', [
            'partner' => $partner,
            'stats'   => $stats,
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
            'company_name' => $request->input('company_name', $partner->company_name),
            'website_url' => $request->input('website_url', $partner->website_url),
            'notes' => $request->input('notes', $partner->notes),
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
            return back()->with('error', 'Cannot delete partner with existing customer records.');
        }

        $partner->delete();

        return redirect()->route('admin.partners.index')->with('success', 'Partner deleted. You can restore it anytime from the deleted partners list.');
    }

    public function restore(int $id): RedirectResponse
    {
        abort_unless(request()->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        $partner = Partner::withTrashed()->findOrFail($id);
        $partner->restore();

        return redirect()->route('admin.partners.index')->with('success', 'Partner restored successfully.');
    }

    public function generateApiKey(Partner $partner): RedirectResponse
    {
        abort_unless(request()->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        $apiKey = $partner->generateApiKey();
        AuditLog::record('partner_api_key_generated', $partner, [], ['partner_id' => $partner->id], request()->user());

        // Store new token in partner settings so external platforms can re-sync
        $partner->forceFill([
            'settings' => array_merge($partner->settings ?? [], [
                'last_token_generated_at' => now()->toIso8601String(),
            ])
        ])->save();

        return back()->with([
            'success'            => 'API key generated successfully.',
            'api_key'            => $apiKey,
            'show_api_key_modal' => true,
        ]);
    }

    public function revokeApiKey(Partner $partner): RedirectResponse
    {
        abort_unless(request()->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        $partner->tokens()->delete();
        AuditLog::record('partner_api_key_revoked', $partner, [], ['partner_id' => $partner->id], request()->user());

        return back()->with('success', 'API key revoked successfully.');
    }

    public function toggleProductAccess(Partner $partner, Request $request): RedirectResponse
    {
        abort_unless(request()->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        $productId = (int) $request->input('product_id');
        $isEnabled = $request->boolean('is_enabled');

        $partner->products()->syncWithoutDetaching([
            $productId => ['is_enabled' => $isEnabled],
        ]);

        return back()->with('success', 'Product access updated successfully.');
    }

}
