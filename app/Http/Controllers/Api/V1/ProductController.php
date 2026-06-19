<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Partner;
use App\Models\Product;
use App\Services\ProductSchemaService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends BaseApiController
{
    public function __construct(private readonly ProductSchemaService $productSchemaService)
    {
    }
    public function index(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $products = Product::query()
            ->where('products.status', Product::STATUS_ACTIVE)
            ->join('partner_product', 'partner_product.product_id', '=', 'products.id')
            ->where('partner_product.partner_id', $partner->id)
            ->where('partner_product.is_enabled', true)
            ->select([
                'products.id',
                'products.product_code',
                'products.name',
                'products.product_name',
                'products.description',
                'products.image',
                'products.status',
                'products.cover_duration_options',
                'products.cover_duration_mode',
                'products.cover_duration_type',
                'products.default_cover_duration_days',
                'products.country',
            ])
            ->get()
            ->map(function ($product) {
                return [
                    'id'                          => $product->id,
                    'product_code'                => $product->product_code,
                    'name'                        => $product->product_name ?: $product->name,
                    'description'                 => $product->description,
                    'status'                      => $product->status,
                    'country'                     => $product->country,
                    'cover_duration_options'      => $product->cover_duration_options,
                    'cover_duration_mode'         => $product->cover_duration_mode,
                    'cover_duration_type'         => $product->cover_duration_type,
                    'default_cover_duration_days' => $product->default_cover_duration_days,
                    'image_url'                   => $product->image
                        ? (str_starts_with($product->image, 'http')
                            ? $product->image
                            : asset('storage/' . $product->image))
                        : null,
                ];
            });

        return $this->success($products);
    }

    public function fields(Request $request, string $uuid): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $product = $this->resolvePartnerProductByUuid($partner, $uuid);

        if (! $product) {
            return $this->error('NOT_FOUND', 'Product not found or not assigned to partner.', [], 404);
        }

        return $this->success([
            'product' => [
                'uuid' => $product->uuid,
                'product_code' => $product->product_code,
                'name' => $product->product_name ?: $product->name,
            ],
            'fields' => $this->formatProductFields($product),
        ]);
    }

    public function schema(Request $request, string $uuid): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $product = $this->resolvePartnerProductByUuid($partner, $uuid);

        if (! $product) {
            return $this->error('NOT_FOUND', 'Product not found or not assigned to partner.', [], 404);
        }

        $schema = $product->api_schema ?: $this->productSchemaService->generate($product);

        return $this->success([
            'product' => [
                'uuid' => $product->uuid,
                'product_code' => $product->product_code,
                'name' => $product->product_name ?: $product->name,
            ],
            'request_schema' => [
                'transaction_payload' => $schema['transaction_payload'] ?? [],
                'product_fields' => $schema['product_fields'] ?? $this->formatProductFields($product),
                'validation_rules' => $schema['validation_rules'] ?? [],
            ],
            'endpoint_base' => $schema['endpoint_base'] ?? "/api/v1/products/{$product->product_code}",
        ]);
    }

    private function resolvePartnerProductByUuid(?Partner $partner, string $uuid): ?Product
    {
        if (! $partner) {
            return null;
        }

        return Product::query()
            ->where('uuid', $uuid)
            ->where('status', Product::STATUS_ACTIVE)
            ->whereHas('partners', fn ($partnerQuery) => $partnerQuery
                ->where('partners.id', $partner->id)
                ->where('partner_product.is_enabled', true))
            ->first();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function formatProductFields(Product $product): array
    {
        return $product->fields()
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($field): array => [
                'field_key' => $field->field_key,
                'label' => $field->label,
                'type' => $field->field_type,
                'required' => (bool) $field->is_required,
                'options' => $field->options ?? [],
            ])
            ->values()
            ->all();
    }
}
