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
            'products' => Product::query()
                ->with(['partners:id,name', 'partnerDirect:id,name'])
                ->paginate(15),
        ]);
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/SuperAdmin/ProductForm', [
            'product' => $product->load('fields'),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['sometimes', 'in:active,inactive'],
        ]));

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
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
