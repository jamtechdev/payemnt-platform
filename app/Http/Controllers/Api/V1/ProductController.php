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
            ->join('partner_product', 'partner_product.product_id', '=', 'products.id')
            ->where('partner_product.partner_id', $partner->id)
            ->where('partner_product.is_enabled', true)
            ->get();

        return response()->json(['status' => 'success', 'data' => $products->map(function ($product) {
            $product->name = $product->product_name ?: $product->name;
            $product->image_url = $product->image ? asset('storage/' . $product->image) : null;

            return $product;
        })]);
    }
}
