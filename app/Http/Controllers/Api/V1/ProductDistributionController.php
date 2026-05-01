<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Partner;
use App\Models\Payment;
use App\Models\PartnerRequestIdempotency;
use App\Models\Product;
use App\Models\WebhookLog;
use App\Services\DynamicProductValidationService;
use App\Services\PartnerTransactionIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Distribution', description: 'Product distribution APIs for partners')]
class ProductDistributionController extends BaseApiController
{
    public function __construct(
        private readonly PartnerTransactionIngestionService $ingestionService,
        private readonly DynamicProductValidationService $dynamicValidationService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/products/{product_code}/submit',
        operationId: 'distributionSubmitPolicy',
        summary: 'Submit customer policy for a product',
        security: [['sanctum' => []]],
        tags: ['Distribution'],
        parameters: [
            new OA\Parameter(name: 'product_code', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'Idempotency-Key', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['transaction_number', 'customer_name', 'customer_email', 'cover_duration'],
                properties: [
                    new OA\Property(property: 'transaction_number', type: 'string', example: 'SWAP-TXN-1001'),
                    new OA\Property(property: 'customer_name', type: 'string', example: 'John Doe'),
                    new OA\Property(property: 'customer_email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+23480000000'),
                    new OA\Property(property: 'policy_number', type: 'string', nullable: true, example: 'POL-1001'),
                    new OA\Property(property: 'cover_duration', type: 'string', example: '12_months'),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'suspended', 'pending', 'cancelled', 'failed']),
                    new OA\Property(property: 'kyc', type: 'object', nullable: true),
                    new OA\Property(property: 'customer_data', type: 'object', nullable: true),
                    new OA\Property(property: 'notes', type: 'string', nullable: true),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'currency', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Submitted'),
            new OA\Response(response: 409, description: 'Idempotency conflict'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function submit(Request $request, string $productCode): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $product = $this->resolvePartnerProduct($partner, $productCode);
        if (! $product) {
            return $this->error('NOT_FOUND', 'Product not found or not assigned to partner.', [], 404);
        }

        $validated = $request->validate([
            'transaction_number' => ['required', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'policy_number' => ['nullable', 'string', 'max:80'],
            'cover_duration' => ['required', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,suspended,pending,cancelled,failed'],
            'kyc' => ['nullable', 'array'],
            'customer_data' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));
        if ($idempotencyKey === '') {
            return $this->error('IDEMPOTENCY_KEY_REQUIRED', 'Idempotency-Key header is required.', [], 422);
        }

        $requestHash = hash('sha256', json_encode($validated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $endpoint = "products/{$productCode}/submit";

        $existing = PartnerRequestIdempotency::query()
            ->where('partner_id', $partner->id)
            ->where('endpoint', $endpoint)
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            if ($existing->request_hash !== $requestHash) {
                return $this->error('IDEMPOTENCY_CONFLICT', 'Idempotency-Key was already used with a different payload.', [], 409);
            }

            return response()->json($existing->response_payload, $existing->status_code);
        }

        if (isset($validated['customer_data']) && $product->relationLoaded('fields')) {
            $validated['customer_data'] = $this->dynamicValidationService->validateAndNormalize($product, $validated['customer_data']);
        }

        $payment = $this->ingestionService->ingest($partner, array_merge($validated, [
            'product_code' => $product->product_code,
            'date_added' => now()->toDateTimeString(),
        ]));

        $response = [
            'status' => 'success',
            'data' => [
                'transaction_number' => $payment->transaction_number,
                'status' => $payment->status,
                'policy_number' => $payment->policy_number,
            ],
            'meta' => (object) [],
        ];

        PartnerRequestIdempotency::query()->create([
            'partner_id' => $partner->id,
            'idempotency_key' => $idempotencyKey,
            'endpoint' => $endpoint,
            'request_hash' => $requestHash,
            'status_code' => 201,
            'response_payload' => $response,
            'expires_at' => now()->addDays(2),
        ]);

        return response()->json($response, 201);
    }

    #[OA\Post(
        path: '/api/v1/products/{product_code}/transactions/{transaction_number}/kyc',
        operationId: 'distributionSubmitKyc',
        summary: 'Submit KYC for an existing transaction',
        security: [['sanctum' => []]],
        tags: ['Distribution'],
        parameters: [
            new OA\Parameter(name: 'product_code', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'transaction_number', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['kyc'],
                properties: [new OA\Property(property: 'kyc', type: 'object')]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'KYC saved')]
    )]
    public function submitKyc(Request $request, string $productCode, string $transactionNumber): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $product = $this->resolvePartnerProduct($partner, $productCode);
        if (! $product) {
            return $this->error('NOT_FOUND', 'Product not found or not assigned to partner.', [], 404);
        }

        $validated = $request->validate([
            'kyc' => ['required', 'array'],
        ]);

        $payment = Payment::query()
            ->where('partner_id', $partner->id)
            ->where('product_id', $product->id)
            ->where('transaction_number', $transactionNumber)
            ->first();

        if (! $payment) {
            return $this->error('NOT_FOUND', 'Transaction not found.', [], 404);
        }

        $payment->update(['kyc_data' => $validated['kyc']]);

        return $this->success(['transaction_number' => $payment->transaction_number, 'kyc_saved' => true]);
    }

    #[OA\Put(
        path: '/api/v1/products/{product_code}/transactions/{transaction_number}',
        operationId: 'distributionUpdatePolicy',
        summary: 'Update existing policy/transaction',
        security: [['sanctum' => []]],
        tags: ['Distribution'],
        parameters: [
            new OA\Parameter(name: 'product_code', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'transaction_number', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'cover_duration', type: 'string', nullable: true),
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'suspended', 'pending', 'cancelled', 'failed']),
                    new OA\Property(property: 'notes', type: 'string', nullable: true),
                    new OA\Property(property: 'policy_number', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Policy updated')]
    )]
    public function updatePolicy(Request $request, string $productCode, string $transactionNumber): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $product = $this->resolvePartnerProduct($partner, $productCode);
        if (! $product) {
            return $this->error('NOT_FOUND', 'Product not found or not assigned to partner.', [], 404);
        }

        $validated = $request->validate([
            'cover_duration' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', 'in:active,suspended,pending,cancelled,failed'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'policy_number' => ['nullable', 'string', 'max:80'],
        ]);

        $payment = Payment::query()
            ->where('partner_id', $partner->id)
            ->where('product_id', $product->id)
            ->where('transaction_number', $transactionNumber)
            ->first();
        if (! $payment) {
            return $this->error('NOT_FOUND', 'Transaction not found.', [], 404);
        }

        $payment->update($validated);

        return $this->success(['transaction_number' => $payment->transaction_number, 'status' => $payment->status]);
    }

    #[OA\Post(
        path: '/api/v1/products/{product_code}/transactions/{transaction_number}/cancel',
        operationId: 'distributionCancelPolicy',
        summary: 'Cancel policy',
        security: [['sanctum' => []]],
        tags: ['Distribution'],
        parameters: [
            new OA\Parameter(name: 'product_code', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'transaction_number', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'Policy cancelled')]
    )]
    public function cancelPolicy(Request $request, string $productCode, string $transactionNumber): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $product = $this->resolvePartnerProduct($partner, $productCode);
        if (! $product) {
            return $this->error('NOT_FOUND', 'Product not found or not assigned to partner.', [], 404);
        }

        $payment = Payment::query()
            ->where('partner_id', $partner->id)
            ->where('product_id', $product->id)
            ->where('transaction_number', $transactionNumber)
            ->first();

        if (! $payment) {
            return $this->error('NOT_FOUND', 'Transaction not found.', [], 404);
        }

        $payment->update(['status' => Payment::STATUS_CANCELLED]);

        return $this->success(['transaction_number' => $payment->transaction_number, 'status' => Payment::STATUS_CANCELLED]);
    }

    #[OA\Get(
        path: '/api/v1/products/{product_code}/fields',
        operationId: 'distributionGetProductFields',
        summary: 'Get partner product fields/schema',
        security: [['sanctum' => []]],
        tags: ['Distribution'],
        parameters: [
            new OA\Parameter(name: 'product_code', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [new OA\Response(response: 200, description: 'Fields returned')]
    )]
    public function getProductFields(Request $request, string $productCode): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $product = $this->resolvePartnerProduct($partner, $productCode);
        if (! $product) {
            return $this->error('NOT_FOUND', 'Product not found or not assigned to partner.', [], 404);
        }

        return $this->success([
            'product_code' => $product->product_code,
            'fields' => $product->fields()->orderBy('sort_order')->get(),
            'schema' => $product->api_schema,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/verify-token',
        operationId: 'distributionVerifyToken',
        summary: 'Verify partner bearer token',
        security: [['sanctum' => []]],
        tags: ['Distribution'],
        responses: [new OA\Response(response: 200, description: 'Token valid')]
    )]
    public function verifyToken(Request $request): JsonResponse
    {
        /** @var Partner|null $partner */
        $partner = $request->attributes->get('partner');

        return $this->success([
            'valid' => true,
            'partner' => [
                'id' => $partner?->id,
                'name' => $partner?->name,
                'code' => $partner?->partner_code,
                'status' => $partner?->status,
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/products/{product_code}/transactions/{transaction_number}/callback',
        operationId: 'distributionWebhookCallback',
        summary: 'Receive webhook callback payload',
        security: [['sanctum' => []]],
        tags: ['Webhooks'],
        parameters: [
            new OA\Parameter(name: 'product_code', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'transaction_number', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Webhook-Signature', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(type: 'object')
        ),
        responses: [new OA\Response(response: 200, description: 'Webhook accepted')]
    )]
    public function webhookCallback(Request $request, string $productCode, string $transactionNumber): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $product = $this->resolvePartnerProduct($partner, $productCode);
        if (! $product) {
            return $this->error('NOT_FOUND', 'Product not found or not assigned to partner.', [], 404);
        }

        $payment = Payment::query()
            ->where('partner_id', $partner->id)
            ->where('product_id', $product->id)
            ->where('transaction_number', $transactionNumber)
            ->first();

        if (! $payment) {
            return $this->error('NOT_FOUND', 'Transaction not found.', [], 404);
        }

        WebhookLog::query()->create([
            'partner_id' => $partner->id,
            'payment_id' => $payment->id,
            'event' => 'partner_callback_received',
            'target_url' => $partner->webhook_url ?? 'callback://partner',
            'payload' => $request->all(),
            'status' => 'sent',
            'status_code' => 200,
            'attempt' => 1,
            'sent_at' => now(),
            'response_body' => 'accepted',
        ]);

        return $this->success(['received' => true]);
    }

    private function resolvePartnerProduct(?Partner $partner, string $productCode): ?Product
    {
        if (! $partner) {
            return null;
        }

        return Product::query()
            ->with('fields')
            ->where('product_code', $productCode)
            ->where(function ($query) use ($partner): void {
                $query->where('partner_id', $partner->id)
                    ->orWhereHas('partners', fn ($partnerQuery) => $partnerQuery
                        ->where('partners.id', $partner->id)
                        ->where('partner_product.is_enabled', true));
            })
            ->first();
    }
}
