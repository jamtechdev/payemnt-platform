<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SwapOffer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SwapOfferController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SwapOffer::query()
            ->with(['customer:id,first_name,last_name,email', 'partner:id,name'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search')->toString();
                $q->where(function ($sub) use ($term) {
                    $sub->where('customer_email', 'like', "%{$term}%")
                        ->orWhere('from_currency_code', 'like', "%{$term}%")
                        ->orWhere('to_currency_code', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->latest('date_added');

        $offers = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/SwapOffers/SwapOfferList', [
            'offers'  => $offers,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(SwapOffer $swapOffer): Response
    {
        $swapOffer->load(['customer:id,first_name,last_name,email,phone', 'partner:id,name']);

        return Inertia::render('Admin/SwapOffers/SwapOfferDetail', [
            'offer' => $swapOffer,
        ]);
    }
}
