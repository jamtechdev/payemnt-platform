<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCustomerController extends BaseApiController
{
    public function __construct(private readonly CustomerRepository $customerRepository)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $perPage = min((int) $request->integer('per_page', 20), 100);
        $customers = $this->customerRepository->listForAdmin($request->all(), $perPage);

        return $this->success([
            'items' => CustomerResource::collection($customers->getCollection()),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'last_page' => $customers->lastPage(),
            ],
        ]);
    }

    public function show(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);
        $customer->load(['partner:id,name,partner_code', 'product:id,name,product_code', 'payments']);
        $pricing = $customer->product
            ? $customer->product->partners()
                ->where('partners.id', $customer->partner_id)
                ->first()?->pivot
            : null;

        $user = auth()->user();
        $canViewPricing = (bool) $user?->hasAnyRole(['partner', 'super_admin', 'reconciliation_admin']);

        if (! $canViewPricing) {
            $maskedPayments = $customer->payments->map(function ($payment): array {
                return [
                    'id' => $payment->uuid,
                    'currency' => $payment->currency,
                    'paid_at' => optional($payment->paid_at)->toIso8601String(),
                    'status' => $payment->status,
                ];
            });

            return $this->success([
                'customer' => new CustomerResource($customer),
                'payments' => $maskedPayments,
                'product_pricing' => null,
            ]);
        }

        return $this->success([
            'customer' => new CustomerResource($customer),
            'payments' => $customer->payments,
            'product_pricing' => [
                'partner_price' => $pricing?->partner_price,
                'partner_currency' => $pricing?->partner_currency,
            ],
        ]);
    }
}
