<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductField;
use App\Models\ProductVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class ProductController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SuperAdmin/ProductList', [
            'products' => Product::query()->with(['fields:id,product_id,field_key,label,field_type,is_required,sort_order'])->withCount(['customers', 'partners', 'fields'])->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/SuperAdmin/ProductForm', ['partners' => Partner::query()->get()]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        DB::transaction(function () use ($request): void {
            $name = $request->string('name')->toString();
            $slug = Str::slug($name);
            $productCode = strtoupper(str_replace('-', '_', $slug));
            $durationOptions = (array) $request->input('cover_duration_options', [12]);
            $defaultDurationMonths = (int) min($durationOptions);
            $product = Product::query()->create([
                'product_code' => $productCode,
                'name' => $name,
                'slug' => $slug,
                'description' => $request->input('description'),
                'status' => $request->input('status', 'active'),
                'cover_duration_mode' => 'custom',
                'default_cover_duration_days' => max(1, $defaultDurationMonths) * 30,
                'cover_duration_options' => $request->input('cover_duration_options', [12]),
            ]);
            foreach ((array) $request->input('fields', []) as $i => $field) {
                $product->fields()->create([
                    'field_key' => (string) ($field['name'] ?? ''),
                    'label' => (string) ($field['label'] ?? ''),
                    'field_type' => (string) ($field['type'] ?? 'text'),
                    'is_required' => (bool) ($field['is_required'] ?? false),
                    'options' => (array) ($field['options'] ?? []),
                    'sort_order' => $i,
                ]);
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('Admin/SuperAdmin/ProductForm', [
            'product' => $product->load('fields', 'partners'),
            'partners' => Partner::query()->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        $payload = $request->only(['name', 'slug', 'description', 'status', 'cover_duration_options']);
        if (! empty($payload['cover_duration_options']) && is_array($payload['cover_duration_options'])) {
            $payload['default_cover_duration_days'] = max(1, (int) min($payload['cover_duration_options'])) * 30;
        }
        $product->update($payload);

        foreach ((array) $request->input('fields', []) as $i => $field) {
            ProductField::query()->updateOrCreate(
                ['id' => $field['id'] ?? null],
                [
                    'field_key' => (string) ($field['name'] ?? ''),
                    'label' => (string) ($field['label'] ?? ''),
                    'field_type' => (string) ($field['type'] ?? 'text'),
                    'is_required' => (bool) ($field['is_required'] ?? false),
                    'options' => (array) ($field['options'] ?? []),
                    'product_id' => $product->id,
                    'sort_order' => $i,
                ]
            );
        }

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

    public function togglePartnerAccess(Request $request, Product $product): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        $validated = $request->validate([
            'partner_id' => ['required', 'integer', 'exists:users,id'],
            'partner_price' => ['nullable', 'numeric', 'min:0'],
            'partner_currency' => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
        ]);

        $partnerId = (int) $request->integer('partner_id');
        $existing = DB::table('partner_products')->where('partner_id', $partnerId)->where('product_id', $product->id)->first();
        $status = $existing && $existing->status === 'active' ? 'inactive' : 'active';
        DB::table('partner_products')->updateOrInsert(
            ['partner_id' => $partnerId, 'product_id' => $product->id],
            [
                'status' => $status,
                'partner_price' => $validated['partner_price'] ?? null,
                'partner_currency' => $validated['partner_currency'] ?? null,
                'activated_at' => $status === 'active' ? now() : null,
                'deactivated_at' => $status === 'inactive' ? now() : null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return back()->with('success', 'Partner access updated.');
    }

    public function versions(Product $product): Response
    {
        return Inertia::render('Admin/SuperAdmin/ProductVersions', [
            'product' => $product->only(['id', 'name', 'slug']),
            'versions' => ProductVersion::query()
                ->where('product_id', $product->id)
                ->orderByDesc('version_number')
                ->paginate(20),
        ]);
    }
}
