<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SuperAdmin/ProductList', [
            'products' => Product::query()->with(['fields:id,product_id,field_key,label,field_type,is_required,sort_order'])->withCount(['customers', 'partners', 'fields'])->paginate(15),
        ]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        abort_unless(request()->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        if ($product->customers()->where('status', 'active')->exists()) {
            return back()->with('error', 'Cannot delete product with active customers.');
        }

        $product->delete();

        return back()->with('success', 'Product deleted.');
    }

    public function versions(Product $product): Response
    {
        return Inertia::render('Admin/SuperAdmin/ProductVersions', [
            'product' => $product->only(['id', 'name', 'slug']),
        ]);
    }
}
