<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\PurchaseRequest;
use App\Models\Product;
use App\Services\PurchaseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class PurchaseController extends BaseApiController
{
    #[OA\Post(
        path: '/api/v1/purchase',
        operationId: 'partnerPurchase',
        summary: 'Record partner purchase',
        description: 'Partner purchase endpoint for Swap Circle and other partners.',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['partner_id', 'product_id', 'customer', 'payment'],
                properties: [
                    new OA\Property(property: 'partner_id', type: 'string', example: 'SWAP_CIRCLE'),
                    new OA\Property(property: 'product_id', type: 'string', example: 'NIGERIA_BENEFICIARY_COMMUNITY'),
                    new OA\Property(property: 'customer', type: 'object'),
                    new OA\Property(property: 'payment', type: 'object'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Purchase recorded'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function store(PurchaseRequest $request, PurchaseService $purchaseService): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        try {
            $product = Product::query()
                ->where('product_code', $request->string('product_id')->toString())
                ->firstOrFail();

            $result = $purchaseService->recordPurchase($partner, $product, $request->validated());
        } catch (ModelNotFoundException) {
            return $this->error('NOT_FOUND', 'Product not found.', status: 404);
        } catch (ValidationException $exception) {
            return $this->error('VALIDATION_ERROR', 'Validation failed.', $exception->errors(), 422);
        }

        return $this->success([
            'customer_id' => $result['customer_id'],
            'message' => 'Purchase recorded successfully',
        ], 201);
    }
}
