<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\Admin\StoreProductRequest;
use App\Models\Product;
use App\Services\ProductSchemaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminProductController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $products = Product::query()->with('fields')->latest()->paginate(min((int) $request->integer('per_page', 20), 100));
        $canViewAllGuidePrice = (bool) $user?->hasRole('super_admin');

        $products->getCollection()->transform(function (Product $product) use ($user, $canViewAllGuidePrice): Product {
            $canView = $canViewAllGuidePrice || (int) $product->guide_price_set_by === (int) $user?->id;
            if (! $canView) {
                $product->guide_price = null;
            }

            return $product;
        });

        return $this->paginated($products);
    }

    public function store(StoreProductRequest $request, ProductSchemaService $productSchemaService): JsonResponse
    {
        $validated = $request->validated();

        $validated['slug'] = Str::slug($validated['name'].'-'.$validated['product_code']);
        $validated['product_name'] = $validated['product_name'] ?? $validated['name'];
        if (isset($validated['image_url'])) {
            $validated['image'] = $validated['image_url'];
            unset($validated['image_url']);
        }
        $product = Product::query()->create($validated);
        foreach ((array) $request->input('fields', []) as $index => $field) {
            $product->fields()->create([
                'field_key' => (string) ($field['field_key'] ?? $field['name'] ?? ''),
                'label' => (string) ($field['label'] ?? ''),
                'field_type' => (string) ($field['field_type'] ?? $field['type'] ?? 'text'),
                'is_required' => (bool) ($field['is_required'] ?? false),
                'is_filterable' => (bool) ($field['is_filterable'] ?? false),
                'options' => (array) ($field['options'] ?? []),
                'validation_rule' => $field['validation_rule'] ?? null,
                'sort_order' => (int) ($field['sort_order'] ?? $index),
            ]);
        }
        $product->update(['api_schema' => $productSchemaService->generate($product)]);

        return $this->success($product->load('fields'), 201);
    }

    public function show(Product $product): JsonResponse
    {
        $user = request()->user();
        $canViewAllGuidePrice = (bool) $user?->hasRole('super_admin');
        $canView = $canViewAllGuidePrice || (int) $product->guide_price_set_by === (int) $user?->id;
        if (! $canView) {
            $product->guide_price = null;
        }

        return $this->success($product->load('fields', 'partners'));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'product_name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'default_cover_duration_days' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'in:active,inactive'],
            'guide_price' => ['sometimes', 'numeric', 'min:0'],
            'settings' => ['nullable', 'array'],
        ]);

        if (array_key_exists('product_name', $validated) && ! array_key_exists('name', $validated)) {
            $validated['name'] = $validated['product_name'];
        }

        $product->update($validated);

        return $this->success($product->fresh('fields'));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();
        return $this->success(['message' => 'Product deleted']);
    }
}
