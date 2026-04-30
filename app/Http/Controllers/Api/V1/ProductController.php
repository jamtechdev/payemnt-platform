<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $products = Product::query()
            ->select([
                'products.id',
                'products.uuid',
                'products.product_code',
                'products.name',
                'products.product_name',
                'products.slug',
                'products.description',
                'products.image',
                'products.status',
                'products.cover_duration_options',
                'products.cover_duration_mode',
                'products.cover_duration_type',
                'products.default_cover_duration_days',
                'products.country',
                'products.guide_price',
                'products.base_price',
                'products.price',
                'partner_product.partner_price',
                'partner_product.partner_currency',
            ])
            ->join('partner_product', 'partner_product.product_id', '=', 'products.id')
            ->where('partner_product.partner_id', $partner->id)
            ->where('partner_product.is_enabled', true)
            ->get();

        return response()->json(['status' => 'success', 'data' => $products->map(function ($product) {
            $fallbackPrice = $product->partner_price
                ?? $product->guide_price
                ?? $product->base_price
                ?? $product->price
                ?? 0;
            $product->name = $product->product_name ?: $product->name;
            $product->effective_price = (float) $fallbackPrice;
            $product->image_url = $product->image ? asset('storage/' . $product->image) : null;
            $product->fields_endpoint = route('api.v1.partner.products.fields', ['uuid' => $product->uuid]);
            $product->schema_endpoint = route('api.v1.partner.products.schema', ['uuid' => $product->uuid]);

            return $product;
        })]);
    }

    public function fields(Request $request, string $uuid): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $product = Product::query()
            ->where('uuid', $uuid)
            ->whereHas('partners', fn ($query) => $query->where('partner_product.partner_id', $partner->id)->where('partner_product.is_enabled', true))
            ->first();

        if (! $product) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'NOT_FOUND',
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $product->fields()->orderBy('sort_order')->get(),
        ]);
    }

    public function schema(Request $request, string $uuid): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $product = Product::query()
            ->with('fields')
            ->where('uuid', $uuid)
            ->whereHas('partners', fn ($query) => $query->where('partner_product.partner_id', $partner->id)->where('partner_product.is_enabled', true))
            ->first();

        if (! $product) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'NOT_FOUND',
                'message' => 'Product not found',
            ], 404);
        }

        $fields = $product->fields->map(function ($field): array {
            return [
                'key' => $field->field_key,
                'label' => $field->label,
                'type' => $field->field_type,
                'required' => (bool) $field->is_required,
                'options' => $field->options,
                'validation_rule' => $field->validation_rule,
            ];
        })->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'product' => [
                    'uuid' => $product->uuid,
                    'product_code' => $product->product_code,
                    'name' => $product->product_name ?? $product->name,
                    'status' => $product->status,
                    'description' => $product->description,
                ],
                'request_schema' => $product->api_schema ?? [
                    'transaction_payload' => [
                        'transaction_number' => 'string|required',
                        'customer_name' => 'string|required',
                        'customer_email' => 'string|required|email',
                        'product_code' => 'string|required',
                        'cover_duration' => 'string|required',
                        'status' => 'active|suspended|pending',
                    ],
                    'product_fields' => $fields,
                ],
            ],
        ]);
    }
}
