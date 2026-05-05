<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PartnerProductController extends BaseApiController
{
    #[OA\Get(
        path: '/api/v1/partner/products',
        operationId: 'partnerProductsList',
        summary: 'Get all products assigned to the authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of assigned products with partner-specific pricing',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'product_code',                type: 'string',  example: 'HEALTH_PLAN_001'),
                                    new OA\Property(property: 'name',                        type: 'string',  example: 'Health Plan'),
                                    new OA\Property(property: 'description',                 type: 'string',  example: 'A health protection plan'),
                                    new OA\Property(property: 'currency_code',               type: 'string',  example: 'USD'),
                                    new OA\Property(property: 'currency_symbol',             type: 'string',  example: '$'),
                                    new OA\Property(property: 'price',                       type: 'number',  example: 120.00),
                                    new OA\Property(property: 'status',                      type: 'string',  example: 'active'),
                                    new OA\Property(property: 'cover_duration_options',      type: 'array',   items: new OA\Items(type: 'integer'), example: [30, 365]),
                                    new OA\Property(property: 'default_cover_duration_days', type: 'integer', example: 365),
                                    new OA\Property(property: 'image_url',                   type: 'string',  nullable: true, example: 'https://example.com/storage/products/abc.jpg'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Missing or invalid Bearer token'),
        ]
    )]
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
                'products.product_code',
                'products.name',
                'products.product_name',
                'products.description',
                'products.image',
                'products.status',
                'products.cover_duration_options',
                'products.default_cover_duration_days',
                'currencies.code as currency_code',
                'currencies.symbol as currency_symbol',
                'partner_product.base_price',
                'partner_product.guide_price',
            ])
            ->get()
            ->map(fn ($p) => [
                'product_code'                => $p->product_code,
                'name'                        => $p->product_name ?: $p->name,
                'description'                 => $p->description,
                'currency_code'               => $p->currency_code,
                'currency_symbol'             => $p->currency_symbol,
                'price'                       => $p->guide_price,
                'status'                      => $p->status,
                'cover_duration_options'      => $p->cover_duration_options,
                'default_cover_duration_days' => $p->default_cover_duration_days,
                'image_url'                   => $p->image
                    ? (str_starts_with($p->image, 'http') ? $p->image : rtrim(config('app.url'), '/').'/storage/'.$p->image)
                    : null,
            ]);

        return $this->success($products);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->error('FORBIDDEN', 'Products can only be created by admin.', [], 403);
    }

    public function destroyByPartner(Request $request): JsonResponse
    {
        return $this->error('FORBIDDEN', 'Not allowed via partner API.', [], 403);
    }

    public function update(Request $request, string $product_code): JsonResponse
    {
        return $this->error('FORBIDDEN', 'Product updates are managed by admin.', [], 403);
    }
}
