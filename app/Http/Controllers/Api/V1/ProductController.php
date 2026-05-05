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
            ->where('products.status', Product::STATUS_ACTIVE)
            ->join('partner_product', 'partner_product.product_id', '=', 'products.id')
            ->join('currencies', 'currencies.id', '=', 'partner_product.currency_id')
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
                'currencies.code as currency_code',
                'currencies.symbol as currency_symbol',
                'partner_product.base_price',
                'partner_product.guide_price',
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
                    'currency_code'               => $product->currency_code,
                    'currency_symbol'             => $product->currency_symbol,
                    'price'                       => $product->guide_price,
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
}
