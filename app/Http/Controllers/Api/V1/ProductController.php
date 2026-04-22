<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class ProductController extends BaseApiController
{
    #[OA\Get(
        path: '/api/v1/partner/products',
        operationId: 'partnerProductsIndex',
        summary: 'List partner products',
        security: [['sanctum' => []]],
        tags: ['Products'],
        responses: [new OA\Response(response: 200, description: 'OK')]
    )]
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
                'products.status',
                'products.cover_duration_options',
                'partner_product.partner_price',
                'partner_product.partner_currency',
            ])
            ->join('partner_product', 'partner_product.product_id', '=', 'products.id')
            ->where('partner_product.partner_id', $partner->id)
            ->where('partner_product.is_enabled', true)
            ->get();

        return response()->json(['status' => 'success', 'data' => $products]);
    }

    #[OA\Get(
        path: '/api/v1/partner/products/{uuid}/fields',
        operationId: 'partnerProductFields',
        summary: 'List product fields',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')]
    )]
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
