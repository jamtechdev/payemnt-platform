<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Customer;
use App\Models\SwapOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class SwapOfferController extends BaseApiController
{
    #[OA\Post(
        path: '/api/v1/swap-offers',
        operationId: 'swapOfferStore',
        summary: 'Create or update a swap offer (partner auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['Swap Offers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['customer_email', 'from_currency_code', 'to_currency_code', 'from_amount', 'to_amount', 'exchange_rate', 'base_amount', 'status', 'date_added'],
                properties: [
                    new OA\Property(property: 'customer_email',      type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'from_currency_code',  type: 'string', example: 'NGN'),
                    new OA\Property(property: 'to_currency_code',    type: 'string', example: 'GHS'),
                    new OA\Property(property: 'from_amount',         type: 'number', format: 'float', example: 5000.00),
                    new OA\Property(property: 'to_amount',           type: 'number', format: 'float', example: 10.50),
                    new OA\Property(property: 'admin_share',         type: 'number', format: 'float', example: 2.50),
                    new OA\Property(property: 'admin_share_amount',  type: 'number', format: 'float', example: 125.00),
                    new OA\Property(property: 'exchange_rate',       type: 'number', format: 'float', example: 0.0021),
                    new OA\Property(property: 'base_amount',         type: 'number', format: 'float', example: 5000.00),
                    new OA\Property(property: 'expiry_date_time',    type: 'string', format: 'date-time', example: '2024-12-31 23:59:59'),
                    new OA\Property(property: 'status',              type: 'string', example: 'Pending'),
                    new OA\Property(property: 'date_added',          type: 'string', format: 'date-time', example: '2024-01-01 10:00:00'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Swap offer created or updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $validated = $request->validate([
            'customer_email'     => ['required', 'email'],
            'from_currency_code' => ['required', 'string', 'max:10'],
            'to_currency_code'   => ['required', 'string', 'max:10'],
            'from_amount'        => ['required', 'numeric', 'min:0'],
            'to_amount'          => ['required', 'numeric', 'min:0'],
            'admin_share'        => ['nullable', 'numeric', 'min:0'],
            'admin_share_amount' => ['nullable', 'numeric', 'min:0'],
            'exchange_rate'      => ['required', 'numeric', 'min:0'],
            'base_amount'        => ['required', 'numeric', 'min:0'],
            'expiry_date_time'   => ['nullable', 'date'],
            'status'             => ['required', 'string', 'max:100'],
            'date_added'         => ['required', 'date'],
        ]);

        $customer = Customer::query()
            ->where('email', $validated['customer_email'])
            ->where('partner_id', $partner->id)
            ->first();

        $existing = SwapOffer::query()
            ->where('customer_email', $validated['customer_email'])
            ->where('partner_id', $partner->id)
            ->where('from_currency_code', $validated['from_currency_code'])
            ->where('to_currency_code', $validated['to_currency_code'])
            ->where('date_added', $validated['date_added'])
            ->first();

        $payload = array_merge($validated, [
            'partner_id'  => $partner->id,
            'customer_id' => $customer?->id,
        ]);

        if ($existing) {
            $existing->update($payload);
            $offer = $existing->fresh();
        } else {
            $offer = SwapOffer::create($payload);
        }

        return $this->success($offer, 200);
    }

    #[OA\Delete(
        path: '/api/v1/swap-offers',
        operationId: 'swapOfferDestroy',
        summary: 'Permanently delete all swap offers of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Swap Offers'],
        responses: [
            new OA\Response(response: 200, description: 'Swap offers deleted'),
            new OA\Response(response: 404, description: 'No swap offers found'),
        ]
    )]
    public function destroy(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $deleted = SwapOffer::where('partner_id', $partner->id)->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No swap offers found for this partner.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }
}
