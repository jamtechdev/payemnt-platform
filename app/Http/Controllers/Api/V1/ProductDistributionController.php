<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Partner;
use App\Models\Payment;
use App\Models\PartnerRequestIdempotency;
use App\Models\Product;
use App\Services\PartnerTransactionIngestionService;
use App\Services\ProductSchemaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class ProductDistributionController extends BaseApiController
{
    public function __construct(
        private readonly PartnerTransactionIngestionService $ingestionService,
        private readonly ProductSchemaService $productSchemaService,
    ) {
    }

    public function getProductFields(Request $request, string $productCode): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $product = $this->resolvePartnerProduct($partner, $productCode);

        if (! $product) {
            return $this->error('NOT_FOUND', 'Product not found or not assigned to partner.', [], 404);
        }

        $schema = $product->api_schema ?: $this->productSchemaService->generate($product);

        return $this->success([
            'product_code' => $product->product_code,
            'fields' => $schema['product_fields'] ?? [],
            'transaction_payload' => $schema['transaction_payload'] ?? [],
            'endpoint_base' => $schema['endpoint_base'] ?? "/api/v1/products/{$product->product_code}",
        ]);
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
                    new OA\Property(property: 'notes', type: 'string', nullable: true),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', nullable: true),
                    new OA\Property(property: 'currency', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Submitted'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Product not assigned to partner'),
            new OA\Response(response: 409, description: 'Idempotency conflict'),
            new OA\Response(response: 422, description: 'Validation error or missing Idempotency-Key'),
        ]
    )]
    public function submit(Request $request, string $productCode): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $validated = $request->validate([
            'transaction_number' => ['required', 'string', 'max:100'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'policy_number' => ['nullable', 'string', 'max:80'],
            'cover_duration' => ['required', 'string', 'max:100'],
            'status' => ['nullable', 'in:active,suspended,pending,cancelled,failed'],
            'kyc' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'product' => ['nullable', 'array'],
            'product.name' => ['nullable', 'string', 'max:255'],
            'product.description' => ['nullable', 'string'],
            'product.price' => ['nullable', 'numeric', 'min:0'],
            'product.currency' => ['nullable', 'string', 'size:3'],
            'product.image_url' => ['nullable', 'string', 'max:2048'],
            'product.status' => ['nullable', 'in:active,inactive'],
        ]);

        $product = $this->resolvePartnerProduct($partner, $productCode)
            ?? $this->createPartnerProductFromSubmittedSnapshot($partner, $productCode, $validated['product'] ?? [], $validated);
        if (! $product) {
            return $this->error('NOT_FOUND', 'Product not found or not assigned to partner.', [], 404);
        }

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
                properties: [
                    new OA\Property(
                        property: 'kyc',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id_type', type: 'string', example: 'phone'),
                            new OA\Property(property: 'id_number', type: 'string', example: '+2348000000000'),
                            new OA\Property(property: 'first_name', type: 'string', example: 'Jane'),
                            new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
                            new OA\Property(property: 'dob', type: 'string', nullable: true),
                            new OA\Property(property: 'address', type: 'string', nullable: true),
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'KYC saved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Product or transaction not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
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

        if ($payment->customer) {
            $payment->customer->update([
                'customer_data' => array_merge(
                    is_array($payment->customer->customer_data) ? $payment->customer->customer_data : [],
                    ['kyc' => $validated['kyc']]
                ),
            ]);
        }

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

    private function resolvePartnerProduct(?Partner $partner, string $productCode): ?Product
    {
        if (! $partner) {
            return null;
        }

        return Product::query()
            ->where('product_code', $productCode)
            ->where('status', Product::STATUS_ACTIVE)
            ->whereHas('partners', fn ($partnerQuery) => $partnerQuery
                ->where('partners.id', $partner->id)
                ->where('partner_product.is_enabled', true))
            ->first();
    }

    private function createPartnerProductFromSubmittedSnapshot(?Partner $partner, string $productCode, array $snapshot, array $salePayload): ?Product
    {
        if (! $partner || trim($productCode) === '' || trim((string) ($snapshot['name'] ?? '')) === '') {
            return null;
        }

        $name = trim((string) $snapshot['name']);
        $currency = strtoupper((string) ($snapshot['currency'] ?? $salePayload['currency'] ?? 'NGN'));
        $amount = (float) ($snapshot['price'] ?? $salePayload['amount'] ?? 0);

        $product = Product::withTrashed()->firstOrNew(['product_code' => $productCode]);
        $product->fill([
            'product_code' => $productCode,
            'partner_id' => $partner->id,
            'partner_code' => $partner->partner_code,
            'name' => $name,
            'product_name' => $name,
            'slug' => $product->slug ?: $this->uniqueProductSlug($name, $productCode),
            'description' => (string) ($snapshot['description'] ?? ''),
            'price' => $amount,
            'base_price' => $amount,
            'guide_price' => $amount,
            'image' => (string) ($snapshot['image_url'] ?? ''),
            'cover_duration_mode' => 'custom',
            'default_cover_duration_days' => $this->coverDurationDays((string) ($salePayload['cover_duration'] ?? '')),
            'cover_duration_options' => [30, 90, 365],
            'status' => Product::STATUS_ACTIVE,
        ]);

        if ($product->trashed()) {
            $product->restore();
        }

        $product->save();
        $this->attachProductToPartner($partner, $product, $amount, $currency);

        return $product;
    }

    private function attachProductToPartner(Partner $partner, Product $product, float $amount, string $currency): void
    {
        $pivot = [
            'is_enabled' => true,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('partner_product', 'base_price')) {
            $pivot['base_price'] = $amount;
        }

        if (Schema::hasColumn('partner_product', 'guide_price')) {
            $pivot['guide_price'] = $amount;
        }

        if (Schema::hasColumn('partner_product', 'currency_id')) {
            $currencyId = \App\Models\Currency::query()->where('code', $currency)->value('id');
            if ($currencyId) {
                $pivot['currency_id'] = $currencyId;
            }
        }

        \Illuminate\Support\Facades\DB::table('partner_product')->updateOrInsert(
            ['partner_id' => $partner->id, 'product_id' => $product->id],
            array_merge(['created_at' => now()], $pivot)
        );
    }

    private function uniqueProductSlug(string $name, string $productCode): string
    {
        $base = Str::slug($name . '-' . $productCode);
        $slug = $base;
        $counter = 2;

        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $counter++;
        }

        return $slug;
    }

    private function coverDurationDays(string $coverDuration): int
    {
        $value = strtolower($coverDuration);
        if (str_contains($value, '365') || str_contains($value, 'annual') || str_contains($value, 'year')) {
            return 365;
        }

        if (str_contains($value, '90')) {
            return 90;
        }

        return 30;
    }
}
