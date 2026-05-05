<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Models\Partner;
use App\Models\Currency;
use App\Models\Product;
use App\Services\ProductSchemaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductSchemaService $productSchemaService)
    {
    }

    public function index(): Response
    {
        $viewer = request()->user();

        return Inertia::render('Admin/SuperAdmin/ProductList', [
            'products' => Product::query()
                ->with([
                    'partners' => fn ($q) => $q
                        ->select('partners.id', 'partners.name')
                        ->withPivot(['is_enabled', 'currency_id', 'base_price', 'guide_price']),
                ])
                ->latest()
                ->paginate(15)
                ->through(function (Product $product): array {
                    $arr = $product->toArray();
                    // Attach currency code to each partner pivot
                    $currencyIds = collect($product->partners)->pluck('pivot.currency_id')->filter()->unique()->values();
                    $currencies = Currency::whereIn('id', $currencyIds)->get()->keyBy('id');
                    $arr['partners'] = collect($product->partners)->map(function ($partner) use ($currencies): array {
                        $p = $partner->toArray();
                        $cid = $partner->pivot->currency_id;
                        $p['pivot']['currency'] = $cid && isset($currencies[$cid])
                            ? $currencies[$cid]->only(['id', 'code', 'symbol'])
                            : null;
                        return $p;
                    })->values()->all();
                    return $arr;
                }),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/SuperAdmin/ProductForm', [
            'partners' => Partner::query()
                ->select(['id', 'name'])
                ->where('status', 'active')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        $validated = $request->validated();

        $product = Product::create([
            'name' => $validated['name'],
            'product_name' => $validated['name'],
            'partner_id' => isset($validated['partner_ids'][0]) ? (int) $validated['partner_ids'][0] : null,
            'product_code' => strtoupper(Str::slug($validated['name'].'-'.Str::random(4), '_')),
            'slug' => Str::slug($validated['name'].'-'.Str::random(4)),
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'status' => $validated['status'],
            'cover_duration_options' => $validated['cover_duration_options'] ?? [365],
            'default_cover_duration_days' => (int) ($validated['cover_duration_options'][0] ?? 365),
            'base_price' => $validated['base_price'] ?? null,
            'price' => $validated['price'] ?? null,
            'guide_price' => $validated['guide_price'] ?? ($validated['price'] ?? null),
            'guide_price_set_by' => $request->user()?->id,
            'created_by' => $request->user()?->id,
            'features' => $validated['features'] ?? null,
            'validation_rules' => $validated['validation_rules'] ?? null,
            'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
            'image' => $request->hasFile('image') ? $request->file('image')?->store('products', 'public') : null,
        ]);

        if (! empty($validated['partner_ids'])) {
            $product->partners()->sync(
                collect($validated['partner_ids'])
                    ->mapWithKeys(fn ($id) => [(int) $id => ['is_enabled' => true]])
                    ->all()
            );
        }

        foreach ((array) ($validated['fields'] ?? []) as $index => $field) {
            $product->fields()->create([
                'field_key' => Str::slug((string) $field['name'], '_'),
                'label' => (string) $field['label'],
                'field_type' => (string) $field['type'],
                'is_required' => (bool) ($field['is_required'] ?? false),
                'options' => $field['options'] ?? null,
                'sort_order' => $index,
            ]);
        }
        $product->update([
            'api_schema'        => $this->productSchemaService->generate($product),
            'api_endpoint'      => "/api/v1/products/{$product->product_code}",
            'api_documentation' => route('partner.api-documentation'),
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Product created.');
    }

    public function show(Product $product): Response
    {
        $partners = $product->partners()
            ->select('partners.id', 'partners.name', 'partners.partner_code', 'partners.contact_email', 'partners.status')
            ->withPivot(['is_enabled', 'currency_id', 'base_price', 'guide_price'])
            ->get();

        $currencyIds = $partners->pluck('pivot.currency_id')->filter()->unique();
        $currencies  = Currency::whereIn('id', $currencyIds)->get()->keyBy('id');

        $partnerPricing = $partners->map(fn ($p) => [
            'id'           => $p->id,
            'name'         => $p->name,
            'partner_code' => $p->partner_code,
            'email'        => $p->contact_email,
            'status'       => $p->status,
            'is_enabled'   => (bool) $p->pivot->is_enabled,
            'currency'     => isset($currencies[$p->pivot->currency_id])
                ? $currencies[$p->pivot->currency_id]->only(['code', 'symbol', 'name'])
                : null,
            'base_price'   => $p->pivot->base_price,
            'guide_price'  => $p->pivot->guide_price,
        ]);

        return Inertia::render('Admin/SuperAdmin/ProductDetail', [
            'product'        => $product->load('fields'),
            'partnerPricing' => $partnerPricing,
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
        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'product_name'=> ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:120'],
            'status'      => ['sometimes', 'in:active,inactive'],
            'partner_ids'  => ['nullable', 'array'],
            'partner_ids.*'=> ['integer', 'exists:partners,id'],
            'base_price'  => ['nullable', 'numeric', 'min:0'],
            'price'       => ['nullable', 'numeric', 'min:0'],
            'guide_price' => ['nullable', 'numeric', 'min:0'],
            'features' => ['nullable', 'array'],
            'validation_rules' => ['nullable', 'array'],
            'terms_and_conditions' => ['nullable', 'string'],
            'fields' => ['nullable', 'array'],
            'fields.*.name' => ['required_with:fields', 'string', 'max:100'],
            'fields.*.label' => ['required_with:fields', 'string', 'max:255'],
            'fields.*.type' => ['required_with:fields', 'in:text,textarea,number,date,datetime,dropdown,boolean,email,phone'],
            'fields.*.is_required' => ['boolean'],
            'fields.*.options' => ['nullable', 'array'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $updateData = collect($validated)->except(['fields', 'image', 'partner_ids'])->all();
        if ($request->hasFile('image')) {
            $updateData['image'] = $request->file('image')->store('products', 'public');
        }
        $product->update($updateData);

        if (! empty($validated['partner_ids'])) {
            $product->partners()->sync(
                collect($validated['partner_ids'])
                    ->mapWithKeys(fn ($id) => [(int) $id => ['is_enabled' => true]])
                    ->all()
            );
        }

        if ($request->filled('price')) {
            $product->forceFill([
                'guide_price_set_by' => $request->user()?->id,
                'guide_price' => $request->input('guide_price', $request->input('price')),
            ])->save();
        }

        if (array_key_exists('fields', $validated)) {
            $product->fields()->delete();
            foreach ((array) $validated['fields'] as $index => $field) {
                $product->fields()->create([
                    'field_key' => Str::slug((string) $field['name'], '_'),
                    'label' => (string) $field['label'],
                    'field_type' => (string) $field['type'],
                    'is_required' => (bool) ($field['is_required'] ?? false),
                    'options' => $field['options'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        }
        $product->update([
            'api_schema'        => $this->productSchemaService->generate($product),
            'api_endpoint'      => "/api/v1/products/{$product->product_code}",
            'api_documentation' => route('partner.api-documentation'),
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function assignPartners(Product $product): Response
    {
        // All partners with their pivot data (enabled or disabled)
        $allAssigned = $product->partners()
            ->select('partners.id', 'partners.name')
            ->withPivot(['is_enabled', 'currency_id', 'base_price', 'guide_price'])
            ->get()
            ->keyBy('id');

        return Inertia::render('Admin/SuperAdmin/ProductAssignPartners', [
            'product'     => $product->only(['id', 'name', 'description', 'image', 'status']),
            'allPartners' => Partner::query()->select(['id', 'name'])->where('status', 'active')->orderBy('name')->get(),
            // Only enabled ones shown as assigned
            'assignedPartners' => $allAssigned->filter(fn ($p) => (bool) $p->pivot->is_enabled)->map(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'is_enabled'  => true,
                'currency_id' => $p->pivot->currency_id,
                'base_price'  => $p->pivot->base_price,
                'guide_price' => $p->pivot->guide_price,
            ])->values(),
            // Disabled ones — pricing saved in DB, ready to restore
            'disabledPartners' => $allAssigned->filter(fn ($p) => ! (bool) $p->pivot->is_enabled)->map(fn ($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'currency_id' => $p->pivot->currency_id,
                'base_price'  => $p->pivot->base_price,
                'guide_price' => $p->pivot->guide_price,
            ])->values(),
            'currencies' => Currency::where('is_active', true)->orderBy('code')->get(['id', 'code', 'name', 'symbol']),
        ]);
    }

    public function syncPartners(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'partners'               => ['nullable', 'array'],
            'partners.*.id'          => ['required', 'integer', 'exists:partners,id'],
            'partners.*.currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'partners.*.base_price'  => ['required', 'numeric', 'min:0'],
            'partners.*.guide_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        foreach ($request->input('partners', []) as $p) {
            $product->partners()->syncWithoutDetaching([
                (int) $p['id'] => [
                    'is_enabled'  => true,
                    'currency_id' => (int) $p['currency_id'],
                    'base_price'  => $p['base_price'],
                    'guide_price' => $p['guide_price'] ?? null,
                ],
            ]);
        }

        return back()->with('success', 'Partner assigned successfully.');
    }

    public function removePartner(Request $request, Product $product): RedirectResponse
    {
        $request->validate(['partner_id' => ['required', 'integer', 'exists:partners,id']]);

        // Just disable — pricing stays in DB
        $product->partners()->syncWithoutDetaching([
            (int) $request->input('partner_id') => ['is_enabled' => false],
        ]);

        return back()->with('success', 'Partner removed.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update([
            'status' => $product->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Product status updated.');
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
