<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
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
                'products.name',
                'products.slug',
                'products.description',
                'products.image',
                'products.status',
                'products.cover_duration_options',
                'products.cover_duration_mode',
                'products.cover_duration_type',
                'products.default_cover_duration_days',
                'products.country',
                'partner_product.partner_price',
                'partner_product.partner_currency',
            ])
            ->join('partner_product', 'partner_product.product_id', '=', 'products.id')
            ->where('partner_product.partner_id', $partner->id)
            ->where('partner_product.is_enabled', true)
            ->get();

        return response()->json(['status' => 'success', 'data' => $products->map(function ($product) {
            $product->image_url = $product->image
                ? asset('storage/' . $product->image)
                : null;
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
}
