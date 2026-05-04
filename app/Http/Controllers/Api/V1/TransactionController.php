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
        summary: 'Create or update a transaction (alternative to POST .../submit + /kyc)',
        description: 'Partner is inferred from Bearer token. Optional header Idempotency-Key: when present, must equal transaction_number.',
        security: [['sanctum' => []]],
        tags: ['Transactions'],
        parameters: [
            new OA\Parameter(
                name: 'Idempotency-Key',
                in: 'header',
                required: false,
                description: 'If set, must match body.transaction_number',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['transaction_number', 'customer_name', 'customer_email', 'product_code', 'cover_duration'],
                properties: [
                    new OA\Property(property: 'transaction_number', type: 'string', example: 'TXN-2026-0001'),
                    new OA\Property(property: 'customer_name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'customer_email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'product_code', type: 'string', example: 'NIGERIA_BENEFICIARY_COMMUNITY'),
                    new OA\Property(property: 'cover_duration', type: 'string', example: '30_days'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+2348000000000'),
                    new OA\Property(property: 'policy_number', type: 'string', nullable: true),
                    new OA\Property(property: 'status', type: 'string', nullable: true, enum: ['active', 'suspended', 'pending', 'cancelled', 'failed']),
                    new OA\Property(property: 'kyc', type: 'object', nullable: true),
                    new OA\Property(property: 'notes', type: 'string', nullable: true),
                    new OA\Property(property: 'date_added', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', nullable: true, example: 739),
                    new OA\Property(property: 'currency', type: 'string', nullable: true, example: 'NGN'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Transaction created or updated'),
            new OA\Response(response: 401, description: 'Missing or invalid Bearer token'),
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
        summary: 'Delete ALL transactions for the authenticated partner (destructive)',
        description: 'Use only in non-production or controlled migrations. Requires Bearer token.',
        security: [['sanctum' => []]],
        tags: ['Transactions'],
        responses: [
            new OA\Response(response: 200, description: 'Transactions deleted'),
            new OA\Response(response: 401, description: 'Unauthorized'),
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
