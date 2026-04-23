<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\PurchaseRequest;
use App\Models\Product;
use App\Services\PurchaseService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PurchaseController extends BaseApiController
{
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
