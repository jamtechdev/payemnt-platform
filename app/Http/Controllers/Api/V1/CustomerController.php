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

class CustomerController extends BaseApiController
{
    /**
     * @deprecated Not routed; live partner ingest is {@see PartnerCustomerController::store}.
     */
    public function storePartnerCustomer(SubmitCustomerRequest $request, CustomerIngestionService $ingestionService): JsonResponse
    {
        return $this->submitCustomer($request, $ingestionService);
    }

    /**
     * @deprecated Not routed; live partner ingest is {@see PartnerCustomerController::store}.
     */
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
                'customer_id' => 'CUST_'.str_pad((string) $customer->id, 6, '0', STR_PAD_LEFT),
                'message' => 'Customer record created successfully',
            ], 201);
        } catch (\Throwable $exception) {
            return $this->error('INTERNAL_ERROR', 'Unable to create customer record.', ['exception' => $exception->getMessage()], 500);
        }
    }

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
