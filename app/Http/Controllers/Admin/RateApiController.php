<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RateApi;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RateApiController extends Controller
{
    public function index(Request $request): Response
    {
        $query = RateApi::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search')->toString();
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('partner_code', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->latest();

        $rateApis = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/RateApis/RateApiList', [
            'rateApis' => $rateApis,
            'filters'  => $request->only(['search', 'status']),
        ]);
    }

    public function show(RateApi $rateApi): Response
    {
        return Inertia::render('Admin/RateApis/RateApiDetail', [
            'rateApi' => $rateApi,
        ]);
    }
}
