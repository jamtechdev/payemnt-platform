<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\SubmitCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Product;
use App\Services\CustomerIngestionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class PartnerCustomerController extends BaseApiController
{
    #[OA\Post(
        path: '/api/v1/customers',
        summary: 'Ingest customer and payment (partner Bearer token)',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/PartnerSubmitCustomer'),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 404, description: 'Product not available for partner'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ]
    )]
    public function store(SubmitCustomerRequest $request, CustomerIngestionService $ingestionService): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        try {
            $product = Product::query()
                ->with('fields')
                ->where('product_code', $request->string('product_id'))
                ->whereHas('partners', function ($query) use ($partner): void {
                    $query->where('partners.id', $partner->id)
                        ->where('partner_product.is_enabled', true);
                })
                ->firstOrFail();

            $customer = $ingestionService->ingest($partner, $product, $request->validated());
        } catch (ValidationException $exception) {
            return $this->error('VALIDATION_ERROR', 'Dynamic field validation failed.', $exception->errors(), 422);
        } catch (ModelNotFoundException) {
            return $this->error('PRODUCT_NOT_AVAILABLE', 'Product is not enabled for this partner.', status: 404);
        }

        return $this->success([
            'customer' => new CustomerResource($customer),
            'latest_payment' => new PaymentResource($customer->payments->sortByDesc('paid_at')->first()),
        ], 201);
    }
}
