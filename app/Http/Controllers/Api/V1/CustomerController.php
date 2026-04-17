<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\AddPaymentRequest;
use App\Http\Requests\Api\V1\SubmitCustomerRequest;
use App\Http\Requests\Api\V1\UpdateCustomerStatusRequest;
use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\CustomerIngestionService;
use App\Services\PartnerTransactionService;
use OpenApi\Attributes as OA;

class CustomerController extends BaseApiController
{
    #[OA\Post(
        path: '/api/v1/partner/customers',
        operationId: 'partnerSubmitCustomer',
        summary: 'Submit customer (partner API)',
        description: 'Preferred partner endpoint. Uses Bearer token from a partner Sanctum token.',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/PartnerSubmitCustomer'),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'customer_uuid', type: 'string', format: 'uuid'),
                                new OA\Property(property: 'customer_id', type: 'string', description: 'Same as customer_uuid (stable external id)'),
                                new OA\Property(property: 'message', type: 'string'),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function storePartnerCustomer(SubmitCustomerRequest $request, CustomerIngestionService $ingestionService): JsonResponse
    {
        return $this->submitCustomer($request, $ingestionService);
    }

    #[OA\Post(
        path: '/api/v1/customers',
        operationId: 'partnerSubmitCustomerAlias',
        summary: 'Submit customer (alias path)',
        deprecated: true,
        description: 'Same behavior as POST /api/v1/partner/customers. Prefer the /partner/customers path.',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/PartnerSubmitCustomer'),
        responses: [
            new OA\Response(response: 201, description: 'Created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function storePartnerCustomerAlias(SubmitCustomerRequest $request, CustomerIngestionService $ingestionService): JsonResponse
    {
        return $this->submitCustomer($request, $ingestionService);
    }

    private function submitCustomer(SubmitCustomerRequest $request, CustomerIngestionService $ingestionService): JsonResponse
    {
        try {
            $partner = $request->attributes->get('partner');
            $customer = $ingestionService->createCustomer($request->validated(), $partner);
            $customer->refresh();

            return $this->success([
                'customer_uuid' => $customer->uuid,
                'customer_id' => $customer->uuid,
                'message' => 'Customer record created successfully',
            ], 201);
        } catch (\Throwable $exception) {
            return $this->error('INTERNAL_ERROR', 'Unable to create customer record.', ['exception' => $exception->getMessage()], 500);
        }
    }

    #[OA\Get(
        path: '/api/v1/partner/customers/{uuid}',
        operationId: 'partnerCustomerShow',
        summary: 'Get customer',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        responses: [new OA\Response(response: 200, description: 'OK'), new OA\Response(response: 404, description: 'Not found')]
    )]
    public function show(Request $request, string $uuid): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $customer = Customer::query()
            ->with(['payments' => fn ($query) => $query->select(['id', 'customer_id', 'amount', 'currency', 'payment_date', 'transaction_reference', 'payment_status'])])
            ->where('uuid', $uuid)
            ->where('partner_id', $partner->id)
            ->first();

        if (! $customer) {
            return $this->error('NOT_FOUND', 'Customer not found', status: 404);
        }

        return $this->success([
            'customer_id' => $customer->uuid,
            'customer_uuid' => $customer->uuid,
            'full_name' => $customer->full_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'status' => $customer->status,
            'cover_start_date' => optional($customer->cover_start_date)->toDateString(),
            'cover_end_date' => optional($customer->cover_end_date)->toDateString(),
            'cover_duration_months' => $customer->cover_duration_months,
            'customer_since' => optional($customer->customer_since)->toDateString(),
            'submitted_data' => $customer->submitted_data,
            'last_payment_date' => optional($customer->payments->first()?->payment_date)->toDateTimeString(),
            'total_lifetime_value' => (float) $customer->payments->sum('amount'),
            'payments' => $customer->payments->map(fn (Payment $payment): array => [
                'payment_uuid' => $payment->uuid,
                'transaction_reference' => $payment->transaction_reference,
                'payment_date' => optional($payment->payment_date)->toDateTimeString(),
                'payment_status' => $payment->payment_status,
                'currency' => $payment->currency,
                'amount' => (float) $payment->amount,
            ])->values(),
        ]);
    }

    #[OA\Patch(
        path: '/api/v1/partner/customers/{uuid}/status',
        operationId: 'partnerCustomerStatusUpdate',
        summary: 'Update customer status',
        security: [['sanctum' => []]],
        tags: ['Customers'],
        parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'expired', 'cancelled']),
                ]
            )
        ),
        responses: [new OA\Response(response: 200, description: 'Updated'), new OA\Response(response: 404, description: 'Not found')]
    )]
    public function updateStatus(UpdateCustomerStatusRequest $request, string $uuid, PartnerTransactionService $transactionService): JsonResponse
    {
        $validated = $request->validated();

        $partner = $request->attributes->get('partner');
        $customer = Customer::query()
            ->where('uuid', $uuid)
            ->where('partner_id', $partner->id)
            ->first();

        if (! $customer) {
            return $this->error('NOT_FOUND', 'Customer not found', status: 404);
        }

        $updated = $transactionService->updateCustomerStatus($customer, $validated['status']);

        return $this->success($updated);
    }

    #[OA\Post(
        path: '/api/v1/partner/customers/{uuid}/payments',
        operationId: 'partnerCustomerPaymentAppend',
        summary: 'Append payment transaction',
        security: [['sanctum' => []]],
        tags: ['Payments'],
        parameters: [new OA\Parameter(name: 'uuid', in: 'path', required: true, schema: new OA\Schema(type: 'string'))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount', 'currency', 'payment_date', 'transaction_reference'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float'),
                    new OA\Property(property: 'currency', type: 'string', example: 'USD'),
                    new OA\Property(property: 'payment_date', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'transaction_reference', type: 'string'),
                    new OA\Property(property: 'payment_status', type: 'string', example: 'success'),
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Created'), new OA\Response(response: 404, description: 'Not found')]
    )]
    public function addPayment(AddPaymentRequest $request, string $uuid, PartnerTransactionService $transactionService): JsonResponse
    {
        $validated = $request->validated();

        $partner = $request->attributes->get('partner');
        $customer = Customer::query()
            ->where('uuid', $uuid)
            ->where('partner_id', $partner->id)
            ->first();

        if (! $customer) {
            return $this->error('NOT_FOUND', 'Customer not found', status: 404);
        }

        $payment = $transactionService->appendPayment($customer, $partner, $validated, [
            'customer_uuid' => $uuid,
            'source' => 'partner_api',
            'payment' => $validated,
        ]);
        $payment->refresh();

        return $this->success([
            'payment_uuid' => $payment->uuid,
            'customer_uuid' => $customer->uuid,
            'transaction_reference' => $payment->transaction_reference,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'payment_date' => optional($payment->payment_date)->toIso8601String(),
            'payment_status' => $payment->payment_status,
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/partner/analytics/usage',
        operationId: 'partnerApiUsageAnalytics',
        summary: 'Partner API usage analytics',
        security: [['sanctum' => []]],
        tags: ['Analytics'],
        responses: [new OA\Response(response: 200, description: 'OK')]
    )]
    public function usageAnalytics(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $customersCount = Customer::query()->where('partner_id', $partner->id)->count();
        $paymentsCount = Payment::query()->where('partner_id', $partner->id)->count();
        $paymentsAmount = (float) Payment::query()->where('partner_id', $partner->id)->sum('amount');
        $lastSubmission = Customer::query()->where('partner_id', $partner->id)->latest('created_at')->value('created_at');

        return $this->success([
            'partner_id' => $partner->id,
            'partner_uuid' => $partner->uuid,
            'customers_submitted' => $customersCount,
            'payments_recorded' => $paymentsCount,
            'total_payment_amount' => $paymentsAmount,
            'last_submission_at' => $lastSubmission,
        ]);
    }
}
