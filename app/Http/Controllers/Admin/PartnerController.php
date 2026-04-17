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

class PartnerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SuperAdmin/PartnerList', [
            'partners' => Partner::query()->withCount('customers')->paginate(15),
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
            'name' => $request->string('name')->toString(),
            'slug' => Str::slug($request->string('name')->toString()),
            'email' => $request->string('email')->toString(),
            'phone' => $request->input('phone'),
            'status' => 'active',
        ]);

        // Role assign alag se karo (safe way)
        $partner->assignRole('partner');

        return redirect()
            ->route('admin.partners.index')
            ->with('success', 'Partner created.');
    }

    public function show(Partner $partner): Response
    {
        return Inertia::render('Admin/SuperAdmin/PartnerDetail', [
            'partner' => $partner->load('products'),
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
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'phone' => $request->input('phone'),
            'status' => $request->input('status', 'inactive'),
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
}
