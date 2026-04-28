<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\ProductsPurchase;
use App\Models\ProductsPurchasesClaim;
use App\Models\ReferralUsage;
use App\Models\SystemCurrency;
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
        summary: 'Create or update a product purchase (partner auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['Products Purchases'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['customer_email', 'product_code', 'product_type', 'cover_duration', 'cover_start_date', 'cover_end_date', 'payment_status'],
                properties: [
                    new OA\Property(property: 'customer_email',    type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'product_code',      type: 'string', example: 'PROD_001'),
                    new OA\Property(property: 'product_type',      type: 'string', example: 'A'),
                    new OA\Property(property: 'cover_duration',    type: 'string', example: 'Monthly'),
                    new OA\Property(property: 'cover_start_date',  type: 'string', format: 'date', example: '2024-01-01'),
                    new OA\Property(property: 'cover_end_date',    type: 'string', format: 'date', example: '2024-12-31'),
                    new OA\Property(property: 'payment_status',    type: 'string', example: 'Successful'),
                    new OA\Property(property: 'transaction_number', type: 'string', example: 'TXN123'),
                    new OA\Property(property: 'date_added',        type: 'string', format: 'date-time', example: '2024-01-01 10:00:00'),
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
        $partner   = $request->attributes->get('partner');
        $validated = $request->validate([
            'customer_email'    => ['required', 'email'],
            'product_code'      => ['required', 'string', 'max:40'],
            'product_type'      => ['required', 'string', 'max:100'],
            'cover_duration'    => ['required', 'string', 'max:100'],
            'cover_start_date'  => ['required', 'date'],
            'cover_end_date'    => ['required', 'date'],
            'payment_status'    => ['required', 'string', 'max:100'],
            'transaction_number' => ['nullable', 'string', 'max:100'],
            'date_added'        => ['nullable', 'date'],
        ]);

        $record = ProductsPurchase::updateOrCreate(
            ['partner_id' => $partner->id, 'customer_email' => $validated['customer_email'], 'transaction_number' => $validated['transaction_number'] ?? null],
            array_merge($validated, ['partner_id' => $partner->id])
        );

        return $this->success($record, 200);
    }

    #[OA\Delete(
        path: '/api/v1/products-purchases',
        operationId: 'productsPurchaseDestroy',
        summary: 'Delete all product purchases of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Products Purchases'],
        responses: [
            new OA\Response(response: 200, description: 'Product purchases deleted'),
            new OA\Response(response: 404, description: 'No records found'),
        ]
    )]
    public function productsPurchaseDestroy(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $deleted = ProductsPurchase::where('partner_id', $partner->id)->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No product purchases found for this partner.', [], 404);
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

    // ─── System Currencies ───────────────────────────────────────────────────────

    #[OA\Post(
        path: '/api/v1/system-currencies',
        operationId: 'systemCurrencyStore',
        summary: 'Create or update a system currency (partner auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['System Currencies'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'code', 'symbol', 'margin', 'admin_rate'],
                properties: [
                    new OA\Property(property: 'name',       type: 'string', example: 'Nigerian Naira'),
                    new OA\Property(property: 'code',       type: 'string', example: 'NGN'),
                    new OA\Property(property: 'symbol',     type: 'string', example: '₦'),
                    new OA\Property(property: 'margin',     type: 'number', format: 'float', example: 2.50),
                    new OA\Property(property: 'admin_rate', type: 'number', format: 'float', example: 1500.00),
                    new OA\Property(property: 'status',     type: 'string', example: 'Active'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Currency created or updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function systemCurrencyStore(Request $request): JsonResponse
    {
        $partner   = $request->attributes->get('partner');
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'code'       => ['required', 'string', 'max:10'],
            'symbol'     => ['required', 'string', 'max:10'],
            'margin'     => ['required', 'numeric', 'min:0'],
            'admin_rate' => ['required', 'numeric', 'min:0'],
            'status'     => ['nullable', 'string', 'max:100'],
        ]);

        $record = SystemCurrency::updateOrCreate(
            ['partner_id' => $partner->id, 'code' => $validated['code']],
            array_merge($validated, ['partner_id' => $partner->id])
        );

        return $this->success($record, 200);
    }

    #[OA\Delete(
        path: '/api/v1/system-currencies',
        operationId: 'systemCurrencyDestroy',
        summary: 'Delete all system currencies of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['System Currencies'],
        responses: [
            new OA\Response(response: 200, description: 'Currencies deleted'),
            new OA\Response(response: 404, description: 'No records found'),
        ]
    )]
    public function systemCurrencyDestroy(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $deleted = SystemCurrency::where('partner_id', $partner->id)->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No currencies found for this partner.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }
}
