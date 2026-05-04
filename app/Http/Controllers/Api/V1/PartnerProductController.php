<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class PartnerProductController extends BaseApiController
{
    #[OA\Get(
        path: '/api/v1/partner/products',
        operationId: 'partnerProductsList',
        summary: 'Get all products available to the authenticated partner (catalog)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Products list (guide_price is never included)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'product_code', type: 'string',  example: 'NIGERIA_BENEFICIARY_COMMUNITY'),
                                    new OA\Property(property: 'name',         type: 'string',  example: 'Beneficiary Community Plan'),
                                    new OA\Property(property: 'description',  type: 'string',  example: 'A community protection plan'),
                                    new OA\Property(property: 'price',        type: 'number',  example: 739.00),
                                    new OA\Property(property: 'status',       type: 'string',  example: 'active'),
                                    new OA\Property(property: 'image_url',    type: 'string',  nullable: true, example: 'https://example.com/image.png'),
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

        // Same visibility as distribution (submit/KYC): owning partner OR pivot assignment with access enabled.
        $products = Product::query()
            ->where('status', Product::STATUS_ACTIVE)
            ->where(function ($query) use ($partner): void {
                $query->where('partner_id', $partner->id)
                    ->orWhereHas('partners', fn ($partnerQuery) => $partnerQuery
                        ->where('partners.id', $partner->id)
                        ->where('partner_product.is_enabled', true));
            })
            ->get()
            ->map(fn (Product $p) => [
                'product_code' => $p->product_code,
                'name'         => $p->product_name ?: $p->name,
                'description'  => $p->description,
                'price'        => $p->price,
                'status'       => $p->status,
                'image_url'    => $p->image
                    ? (str_starts_with($p->image, 'http') ? $p->image : rtrim(config('app.url'), '/').'/storage/'.$p->image)
                    : null,
            ]);

        return $this->success($products);
    }

    // POST, PUT, DELETE — internal use only, hidden from Swagger
    public function store(Request $request): JsonResponse
    {
        return $this->error(
            'FORBIDDEN',
            'Products can only be created by admin. Partner API supports update of assigned products only.',
            [],
            403
        );
    }

    // DELETE — internal use only, hidden from Swagger
    public function destroyByPartner(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $deleted = Product::withTrashed()
            ->where('partner_id', $partner->id)
            ->forceDelete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No products found for this partner.', status: 404);
        }

        return $this->success(['deleted_count' => $deleted], 200);
    }

    // PUT — internal use only, hidden from Swagger
    public function update(Request $request, string $product_code): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $product = Product::query()
            ->where('product_code', $product_code)
            ->where('partner_id', $partner->id)
            ->first();

        if (! $product) {
            return $this->error('NOT_FOUND', 'Product not found.', status: 404);
        }

        $validated = $request->validate([
            'image_url'   => ['nullable', 'string', 'max:500'],
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['nullable', 'numeric', 'min:0'],
            'status'      => ['sometimes', 'in:active,inactive'],
        ]);

        if (array_key_exists('image_url', $validated)) {
            $validated['image'] = $validated['image_url'];
            unset($validated['image_url']);
        }

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name'] . '-' . $product->product_code);
        }

        if (array_key_exists('price', $validated)) {
            $validated['base_price'] = $validated['price'];
            unset($validated['price']);
        }

        $product->update($validated);

        return $this->success($product->fresh());
    }
}
