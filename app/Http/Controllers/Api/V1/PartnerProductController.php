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
    #[OA\Post(
        path: '/api/v1/partner/products',
        operationId: 'partnerProductCreate',
        summary: 'Create or update a product (partner auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['product_code', 'name', 'status'],
                properties: [
                    new OA\Property(property: 'product_code', type: 'string', example: 'PROD_001', description: 'Unique product code'),
                    new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'https://example.com/image.png'),
                    new OA\Property(property: 'name', type: 'string', example: 'Beneficiary Community Plan'),
                    new OA\Property(property: 'description', type: 'string', nullable: true, example: 'A community protection plan'),
                    new OA\Property(property: 'price', type: 'number', format: 'float', nullable: true, example: 29.99),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'], example: 'active'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product created or updated'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    // public function store(Request $request): JsonResponse
    // {
    //     $partner = $request->attributes->get('partner');

    //     $validated = $request->validate([
    //         'partner_id'   => ['required', 'integer'],
    //         'partner_code' => ['required', 'string', 'max:40'],
    //         'product_code' => ['required', 'string', 'max:40', 'unique:products,product_code'],
    //         'image_url'    => ['nullable', 'string', 'max:500'],
    //         'name'         => ['required', 'string', 'max:255'],
    //         'description'  => ['nullable', 'string'],
    //         'price'        => ['nullable', 'numeric', 'min:0'],
    //         'status'       => ['required', 'in:active,inactive'],
    //     ]);

    //     $validated['slug']  = Str::slug($validated['name'] . '-' . $validated['product_code']);
    //     $validated['image'] = $validated['image_url'] ?? null;
    //     unset($validated['image_url']);

    //     $product = Product::query()->create($validated);

    //     return $this->success($product, 201);
    // }
    public function store(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $validated = $request->validate([
            'product_code' => ['required', 'string', 'max:40'],
            'image_url'    => ['nullable', 'string', 'max:500'],
            'name'         => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string'],
            'price'        => ['nullable', 'numeric', 'min:0'],
            'status'       => ['required', 'in:active,inactive'],
        ]);

        $validated['slug']       = Str::slug($validated['name'] . '-' . $validated['product_code']);
        $validated['image']      = $validated['image_url'] ?? null;
        $validated['partner_id'] = $partner->id;

        unset($validated['image_url']);

        $product = Product::updateOrCreate(
            [
                'product_code' => $validated['product_code'],
                'partner_id'   => $partner->id,
            ],
            $validated
        );

        return $this->success($product, 200);
    }

    #[OA\Put(
        path: '/api/v1/partner/products/{product_code}',
        operationId: 'partnerProductUpdate',
        summary: 'Update an existing product (Partner API Key)',
        security: [['sanctum' => []]],
        tags: ['Products'],
        parameters: [
            new OA\Parameter(name: 'product_code', in: 'path', required: true, schema: new OA\Schema(type: 'string'), example: 'PROD_001'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'https://example.com/image.png'),
                    new OA\Property(property: 'name', type: 'string', example: 'Updated Plan Name'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'price', type: 'number', format: 'float', nullable: true, example: 49.99),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'], example: 'active'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product updated successfully'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    #[OA\Delete(
        path: '/api/v1/partner/products',
        operationId: 'partnerProductsDeleteByPartner',
        summary: 'Delete all products of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Products'],
        responses: [
            new OA\Response(response: 200, description: 'Products deleted successfully'),
            new OA\Response(response: 404, description: 'No products found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
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

        $product->update($validated);

        return $this->success($product->fresh());
    }
}
