<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Faq::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search')->toString();
                $q->where(function ($sub) use ($term) {
                    $sub->where('question', 'like', "%{$term}%")
                        ->orWhere('faq_code', 'like', "%{$term}%")
                        ->orWhere('partner_code', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->latest();

        $faqs = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Faqs/FaqList', [
            'faqs'    => $faqs,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Faq $faq): Response
    {
        return Inertia::render('Admin/Faqs/FaqDetail', [
            'faq' => $faq,
        ]);
    }
}
