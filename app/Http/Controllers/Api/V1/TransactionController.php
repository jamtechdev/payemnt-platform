<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StorePartnerTransactionRequest;
use App\Http\Resources\Api\V1\PartnerTransactionResource;
use App\Models\Payment;
use App\Services\PartnerTransactionIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class TransactionController extends BaseApiController
{
    #[OA\Post(
        path: '/api/v1/transactions',
        operationId: 'transactionStore',
        summary: 'Create or update a transaction (partner auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['Transactions'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['transaction_number', 'customer_email', 'product_code', 'payment_status', 'date_added'],
                properties: [
                    new OA\Property(property: 'transaction_number', type: 'string', example: 'TXN123'),
                    new OA\Property(property: 'customer_email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'product_code', type: 'string', example: 'PROD_001'),
                    new OA\Property(property: 'product_type', type: 'string', example: 'A'),
                    new OA\Property(property: 'cover_duration', type: 'string', example: 'Monthly'),
                    new OA\Property(property: 'cover_start_date', type: 'string', format: 'date', example: '2024-01-01'),
                    new OA\Property(property: 'cover_end_date', type: 'string', format: 'date', example: '2024-12-31'),
                    new OA\Property(property: 'payment_status', type: 'string', example: 'Successful'),
                    new OA\Property(property: 'payment_message', type: 'string', example: 'Payment done'),
                    new OA\Property(property: 'stripe_payment_intent', type: 'string', example: 'pi_xxx'),
                    new OA\Property(property: 'stripe_payment_status', type: 'string', example: 'succeeded'),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', nullable: true, example: 20000),
                    new OA\Property(property: 'currency', type: 'string', nullable: true, example: 'NGN'),
                    new OA\Property(property: 'date_added', type: 'string', format: 'date-time', example: '2024-01-01 10:00:00'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Transaction created or updated'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 404, description: 'Customer or Product not found'),
        ]
    )]
    public function store(StorePartnerTransactionRequest $request, PartnerTransactionIngestionService $ingestionService): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $validated = $request->validated();

        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey && $idempotencyKey !== $validated['transaction_number']) {
            return $this->error('VALIDATION_ERROR', 'Idempotency-Key must match transaction_number when provided.', [], 422);
        }

        $normalizedPayload = [
            'transaction_number' => $validated['transaction_number'],
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'phone' => $validated['phone'] ?? null,
            'policy_number' => $validated['policy_number'] ?? null,
            'product_code' => $validated['product_code'],
            'cover_duration' => $validated['cover_duration'],
            'status' => $validated['status'] ?? Payment::STATUS_PENDING,
            'notes' => $validated['notes'] ?? null,
            'kyc' => $validated['kyc'] ?? null,
            'date_added' => $validated['date_added'] ?? now()->toDateTimeString(),
            'amount' => $validated['amount'] ?? 0,
            'currency' => $validated['currency'] ?? 'USD',
        ];

        $payment = $ingestionService->ingest($partner, $normalizedPayload);

        return $this->success(new PartnerTransactionResource($payment), 200);
    }

    #[OA\Delete(
        path: '/api/v1/transactions',
        operationId: 'transactionDestroy',
        summary: 'Permanently delete all transactions of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Transactions'],
        responses: [
            new OA\Response(response: 200, description: 'Transactions deleted'),
            new OA\Response(response: 404, description: 'No transactions found'),
        ]
    )]
    public function destroy(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $deleted = Payment::where('partner_id', $partner->id)->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No transactions found for this partner.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }
}
