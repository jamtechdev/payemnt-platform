<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
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
    public function store(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $validated = $request->validate([
            'transaction_number'    => ['required', 'string', 'max:100'],
            'customer_email'        => ['required', 'email'],
            'product_code'          => ['required', 'string', 'max:40'],
            'product_type'          => ['nullable', 'string', 'max:100'],
            'cover_duration'        => ['nullable', 'string', 'max:100'],
            'cover_start_date'      => ['nullable', 'date'],
            'cover_end_date'        => ['nullable', 'date'],
            'payment_status'        => ['required', 'string', 'max:100'],
            'payment_message'       => ['nullable', 'string', 'max:500'],
            'stripe_payment_intent' => ['nullable', 'string', 'max:255'],
            'stripe_payment_status' => ['nullable', 'string', 'max:100'],
            'date_added'            => ['required', 'date'],
        ]);

        $customer = Customer::query()
            ->where('email', $validated['customer_email'])
            ->where('partner_id', $partner->id)
            ->first();

        if (! $customer) {
            return $this->error('NOT_FOUND', 'Customer not found for this partner.', [], 404);
        }

        $product = Product::query()
            ->where('product_code', $validated['product_code'])
            ->where('partner_id', $partner->id)
            ->first();

        if (! $product) {
            return $this->error('NOT_FOUND', 'Product not found for this partner.', [], 404);
        }

        $existing = Payment::query()
            ->where('transaction_number', $validated['transaction_number'])
            ->where('partner_id', $partner->id)
            ->first();

        if ($existing) {
            $existing->update([
                'product_type'          => $validated['product_type'] ?? null,
                'cover_duration'        => $validated['cover_duration'] ?? null,
                'cover_start_date'      => $validated['cover_start_date'] ?? null,
                'cover_end_date'        => $validated['cover_end_date'] ?? null,
                'status'                => $validated['payment_status'],
                'payment_message'       => $validated['payment_message'] ?? null,
                'stripe_payment_intent' => $validated['stripe_payment_intent'] ?? null,
                'stripe_payment_status' => $validated['stripe_payment_status'] ?? null,
                'paid_at'               => $validated['date_added'],
            ]);
            $payment = $existing->fresh();
        } else {
            $payment = Payment::create([
                'transaction_number'    => $validated['transaction_number'],
                'customer_id'           => $customer->id,
                'partner_id'            => $partner->id,
                'product_id'            => $product->id,
                'product_type'          => $validated['product_type'] ?? null,
                'cover_duration'        => $validated['cover_duration'] ?? null,
                'cover_start_date'      => $validated['cover_start_date'] ?? null,
                'cover_end_date'        => $validated['cover_end_date'] ?? null,
                'amount'                => 0,
                'currency'              => 'USD',
                'status'                => $validated['payment_status'],
                'payment_message'       => $validated['payment_message'] ?? null,
                'stripe_payment_intent' => $validated['stripe_payment_intent'] ?? null,
                'stripe_payment_status' => $validated['stripe_payment_status'] ?? null,
                'paid_at'               => $validated['date_added'],
            ]);
        }

        return $this->success($payment, 200);
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
