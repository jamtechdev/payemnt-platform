<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Occupation;
use App\Models\ProductsPurchase;
use App\Models\ProductsPurchasesClaim;
use App\Models\ReferralUsage;
use App\Models\Relationship;
use App\Models\SystemCurrency;
use App\Models\TaskType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppResourceController extends Controller
{
    public function taskTypes(Request $request): Response
    {
        $items = TaskType::query()
            ->with('partner:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/AppResources/TaskTypeList', [
            'items'   => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function occupations(Request $request): Response
    {
        $items = Occupation::query()
            ->with('partner:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/AppResources/OccupationList', [
            'items'   => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function relationships(Request $request): Response
    {
        $items = Relationship::query()
            ->with('partner:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/AppResources/RelationshipList', [
            'items'   => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function referralUsages(Request $request): Response
    {
        $items = ReferralUsage::query()
            ->with('partner:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where('refer_code', 'like', '%'.$request->string('search').'%')
                ->orWhere('referrer_email', 'like', '%'.$request->string('search').'%')
                ->orWhere('used_by_email', 'like', '%'.$request->string('search').'%'))
            ->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/AppResources/ReferralUsageList', [
            'items'   => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function productsPurchases(Request $request): Response
    {
        $items = ProductsPurchase::query()
            ->with('partner:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where('customer_email', 'like', '%'.$request->string('search').'%')
                ->orWhere('transaction_number', 'like', '%'.$request->string('search').'%')
                ->orWhere('product_code', 'like', '%'.$request->string('search').'%'))
            ->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/AppResources/ProductsPurchaseList', [
            'items'   => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function productsPurchasesClaims(Request $request): Response
    {
        $items = ProductsPurchasesClaim::query()
            ->with('partner:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where('customer_email', 'like', '%'.$request->string('search').'%')
                ->orWhere('product_code', 'like', '%'.$request->string('search').'%'))
            ->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/AppResources/ProductsPurchasesClaimList', [
            'items'   => $items,
            'filters' => $request->only(['search']),
        ]);
    }

    public function systemCurrencies(Request $request): Response
    {
        $items = SystemCurrency::query()
            ->with('partner:id,name')
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('code', 'like', '%'.$request->string('search').'%'))
            ->latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/AppResources/SystemCurrencyList', [
            'items'   => $items,
            'filters' => $request->only(['search']),
        ]);
    }
}
