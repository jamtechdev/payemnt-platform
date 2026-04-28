<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConnectCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConnectCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ConnectCategory::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->string('search')->toString();
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('category_code', 'like', "%{$term}%")
                        ->orWhere('partner_code', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->latest();

        $categories = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/ConnectCategories/ConnectCategoryList', [
            'categories' => $categories,
            'filters'    => $request->only(['search', 'status']),
        ]);
    }

    public function show(ConnectCategory $connectCategory): Response
    {
        return Inertia::render('Admin/ConnectCategories/ConnectCategoryDetail', [
            'category' => $connectCategory,
        ]);
    }
}
