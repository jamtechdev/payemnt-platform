<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\ProductsPurchase;
use App\Models\ProductsPurchasesClaim;
use App\Models\ReferralUsage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DataController extends BaseApiController
{
    // ─── Referral Usages ────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/api/v1/referral-usages',
        operationId: 'referralUsageStore',
        summary: 'Create or update a referral usage (partner auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['Referral Usages'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['referrer_email', 'used_by_email', 'refer_code'],
                properties: [
                    new OA\Property(property: 'referrer_email', type: 'string', format: 'email', example: 'referrer@example.com'),
                    new OA\Property(property: 'used_by_email',  type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'refer_code',     type: 'string', example: 'REF123'),
                    new OA\Property(property: 'date_used',      type: 'string', format: 'date-time', example: '2024-01-01 10:00:00'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Referral usage created or updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function referralUsageStore(Request $request): JsonResponse
    {
        $partner   = $request->attributes->get('partner');
        $validated = $request->validate([
            'referrer_email' => ['required', 'email'],
            'used_by_email'  => ['required', 'email'],
            'refer_code'     => ['required', 'string', 'max:100'],
            'date_used'      => ['nullable', 'date'],
        ]);

        $record = ReferralUsage::updateOrCreate(
            ['partner_id' => $partner->id, 'refer_code' => $validated['refer_code'], 'used_by_email' => $validated['used_by_email']],
            array_merge($validated, ['partner_id' => $partner->id])
        );

        return $this->success($record, 200);
    }

    #[OA\Delete(
        path: '/api/v1/referral-usages',
        operationId: 'referralUsageDestroy',
        summary: 'Delete all referral usages of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Referral Usages'],
        responses: [
            new OA\Response(response: 200, description: 'Referral usages deleted'),
            new OA\Response(response: 404, description: 'No records found'),
        ]
    )]
    public function referralUsageDestroy(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $deleted = ReferralUsage::where('partner_id', $partner->id)->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No referral usages found for this partner.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }

    // ─── Products Purchases ──────────────────────────────────────────────────────

    #[OA\Post(
        path: '/api/v1/products-purchases',
        operationId: 'productsPurchaseStore',
        summary: 'Create or update a product purchase',
        security: [['sanctum' => []]],
        tags: ['Products Purchases'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['swap_offers_requests_id', 'from_users_customers_id', 'to_users_customers_id', 'from_system_currencies_id', 'to_system_currencies_id', 'from_amount', 'to_amount', 'admin_share', 'admin_share_amount', 'system_currencies_id', 'base_amount', 'payment_method_id', 'status'],
                properties: [
                    new OA\Property(property: 'swap_offers_requests_id',  type: 'integer', example: 1),
                    new OA\Property(property: 'from_users_customers_id',  type: 'integer', example: 101),
                    new OA\Property(property: 'to_users_customers_id',    type: 'integer', example: 102),
                    new OA\Property(property: 'from_system_currencies_id', type: 'integer', example: 3),
                    new OA\Property(property: 'to_system_currencies_id',  type: 'integer', example: 5),
                    new OA\Property(property: 'from_amount',              type: 'number', format: 'float', example: 500.00),
                    new OA\Property(property: 'to_amount',                type: 'number', format: 'float', example: 450.00),
                    new OA\Property(property: 'admin_share',              type: 'number', format: 'float', example: 2.5),
                    new OA\Property(property: 'admin_share_amount',       type: 'number', format: 'float', example: 12.50),
                    new OA\Property(property: 'system_currencies_id',     type: 'integer', example: 1),
                    new OA\Property(property: 'base_amount',              type: 'number', format: 'float', example: 500.00),
                    new OA\Property(property: 'payment_method_id',        type: 'integer', example: 1),
                    new OA\Property(property: 'status',                   type: 'string', example: 'completed'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Product purchase created or updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function productsPurchaseStore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'swap_offers_requests_id'  => ['required', 'integer'],
            'from_users_customers_id'  => ['required', 'integer'],
            'to_users_customers_id'    => ['required', 'integer'],
            'from_system_currencies_id' => ['required', 'integer'],
            'to_system_currencies_id'  => ['required', 'integer'],
            'from_amount'              => ['required', 'numeric', 'min:0'],
            'to_amount'                => ['required', 'numeric', 'min:0'],
            'admin_share'              => ['required', 'numeric', 'min:0'],
            'admin_share_amount'       => ['required', 'numeric', 'min:0'],
            'system_currencies_id'     => ['required', 'integer'],
            'base_amount'              => ['required', 'numeric', 'min:0'],
            'payment_method_id'        => ['required', 'integer'],
            'status'                   => ['required', 'string', 'max:100'],
        ]);

        $record = ProductsPurchase::updateOrCreate(
            ['swap_offers_requests_id' => $validated['swap_offers_requests_id']],
            $validated
        );

        return $this->success($record, 200);
    }

    #[OA\Delete(
        path: '/api/v1/products-purchases',
        operationId: 'productsPurchaseDestroy',
        summary: 'Delete a product purchase by swap_offers_requests_id',
        security: [['sanctum' => []]],
        tags: ['Products Purchases'],
        responses: [
            new OA\Response(response: 200, description: 'Product purchases deleted'),
            new OA\Response(response: 404, description: 'No records found'),
        ]
    )]
    public function productsPurchaseDestroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'swap_offers_requests_id' => ['required', 'integer'],
        ]);

        $deleted = ProductsPurchase::where('swap_offers_requests_id', $validated['swap_offers_requests_id'])->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No product purchases found.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }

    // ─── Products Purchases Claims ───────────────────────────────────────────────

    #[OA\Post(
        path: '/api/v1/products-purchases-claims',
        operationId: 'productsPurchasesClaimStore',
        summary: 'Create or update a product purchase claim (partner auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['Products Purchases Claims'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['customer_email', 'product_code', 'date'],
                properties: [
                    new OA\Property(property: 'customer_email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'product_code',   type: 'string', example: 'PROD_001'),
                    new OA\Property(property: 'date',           type: 'string', format: 'date', example: '2024-01-01'),
                    new OA\Property(property: 'description',    type: 'string', example: 'Claim description'),
                    new OA\Property(property: 'acknowledged',   type: 'string', example: 'No'),
                    new OA\Property(property: 'date_added',     type: 'string', format: 'date-time', example: '2024-01-01 10:00:00'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Claim created or updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function productsPurchasesClaimStore(Request $request): JsonResponse
    {
        $partner   = $request->attributes->get('partner');
        $validated = $request->validate([
            'customer_email' => ['required', 'email'],
            'product_code'   => ['required', 'string', 'max:40'],
            'date'           => ['required', 'date'],
            'description'    => ['nullable', 'string'],
            'acknowledged'   => ['nullable', 'string', 'max:10'],
            'date_added'     => ['nullable', 'date'],
        ]);

        $record = ProductsPurchasesClaim::updateOrCreate(
            ['partner_id' => $partner->id, 'customer_email' => $validated['customer_email'], 'product_code' => $validated['product_code'], 'date' => $validated['date']],
            array_merge($validated, ['partner_id' => $partner->id])
        );

        return $this->success($record, 200);
    }

    #[OA\Delete(
        path: '/api/v1/products-purchases-claims',
        operationId: 'productsPurchasesClaimDestroy',
        summary: 'Delete all product purchase claims of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Products Purchases Claims'],
        responses: [
            new OA\Response(response: 200, description: 'Claims deleted'),
            new OA\Response(response: 404, description: 'No records found'),
        ]
    )]
    public function productsPurchasesClaimDestroy(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $deleted = ProductsPurchasesClaim::where('partner_id', $partner->id)->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No claims found for this partner.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }
}
