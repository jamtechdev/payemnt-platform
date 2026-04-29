<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\FundWallet;
use App\Models\ProductsPurchase;
use App\Models\ProductsPurchasesClaim;
use App\Models\ReferralUsage;
use App\Models\WithdrawWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DataController extends BaseApiController
{
    // ─── Withdraw Wallets ────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/api/v1/withdraw-wallets',
        operationId: 'withdrawWalletStore',
        summary: 'Create or update multiple withdraw wallet entries (partner auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['Withdraw Wallets'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['data'],
                properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(
                            required: ['customer_email', 'amount', 'currency_code', 'status'],
                            properties: [
                                new OA\Property(property: 'customer_email', type: 'string', format: 'email', example: 'user@example.com'),
                                new OA\Property(property: 'amount',         type: 'number', format: 'float', example: 1000.00),
                                new OA\Property(property: 'description',    type: 'string', example: 'Withdrawal request'),
                                new OA\Property(property: 'currency_code',  type: 'string', example: 'NGN'),
                                new OA\Property(property: 'status',         type: 'string', enum: ['Pending', 'Approved', 'Rejected'], example: 'Pending'),
                                new OA\Property(property: 'date_added',     type: 'string', format: 'date-time', example: '2024-01-01 10:00:00'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Withdraw wallets created or updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function withdrawWalletStore(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $request->validate([
            'data'                    => ['required', 'array', 'min:1'],
            'data.*.customer_email'   => ['required', 'email'],
            'data.*.amount'           => ['required', 'numeric', 'min:0'],
            'data.*.description'      => ['nullable', 'string'],
            'data.*.currency_code'    => ['required', 'string', 'max:10'],
            'data.*.status'           => ['required', 'string', 'in:Pending,Approved,Rejected'],
            'data.*.date_added'       => ['nullable', 'date'],
        ]);

        $records = [];
        foreach ($request->input('data') as $item) {
            $records[] = WithdrawWallet::create(
                array_merge($item, ['partner_id' => $partner->id])
            );
        }

        return $this->success($records, 200);
    }

    #[OA\Delete(
        path: '/api/v1/withdraw-wallets',
        operationId: 'withdrawWalletDestroy',
        summary: 'Delete all withdraw wallet entries of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Withdraw Wallets'],
        responses: [
            new OA\Response(response: 200, description: 'Withdraw wallet entries deleted'),
            new OA\Response(response: 404, description: 'No records found'),
        ]
    )]
    public function withdrawWalletDestroy(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $deleted = WithdrawWallet::where('partner_id', $partner->id)->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No withdraw wallet entries found for this partner.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }

    // ─── Fund Wallets ────────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/api/v1/fund-wallets',
        operationId: 'fundWalletStore',
        summary: 'Create or update a fund wallet entry (partner auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['Fund Wallets'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['customer_email', 'bank_name', 'amount', 'status'],
                properties: [
                    new OA\Property(property: 'customer_email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'bank_name',      type: 'string', example: 'Access Bank'),
                    new OA\Property(property: 'amount',         type: 'number', format: 'float', example: 5000.00),
                    new OA\Property(property: 'description',    type: 'string', example: 'Wallet top-up'),
                    new OA\Property(property: 'image_url',      type: 'string', example: 'https://example.com/proof.jpg'),
                    new OA\Property(property: 'status',         type: 'string', enum: ['Pending', 'Funded', 'Rejected'], example: 'Pending'),
                    new OA\Property(property: 'date_added',     type: 'string', format: 'date-time', example: '2024-01-01 10:00:00'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Fund wallet created or updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function fundWalletStore(Request $request): JsonResponse
    {
        $partner   = $request->attributes->get('partner');
        $validated = $request->validate([
            'customer_email' => ['required', 'email'],
            'bank_name'      => ['required', 'string', 'max:150'],
            'amount'         => ['required', 'numeric', 'min:0'],
            'description'    => ['nullable', 'string'],
            'image_url'      => ['nullable', 'string', 'max:500'],
            'status'         => ['required', 'string', 'in:Pending,Funded,Rejected'],
            'date_added'     => ['nullable', 'date'],
        ]);

        $record = FundWallet::updateOrCreate(
            ['partner_id' => $partner->id, 'customer_email' => $validated['customer_email'], 'bank_name' => $validated['bank_name']],
            array_merge($validated, ['partner_id' => $partner->id])
        );

        return $this->success($record, 200);
    }

    #[OA\Delete(
        path: '/api/v1/fund-wallets',
        operationId: 'fundWalletDestroy',
        summary: 'Delete all fund wallet entries of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Fund Wallets'],
        responses: [
            new OA\Response(response: 200, description: 'Fund wallet entries deleted'),
            new OA\Response(response: 404, description: 'No records found'),
        ]
    )]
    public function fundWalletDestroy(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $deleted = FundWallet::where('partner_id', $partner->id)->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No fund wallet entries found for this partner.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }

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
                required: ['swap_offers_requests_id', 'from_user_name', 'from_user_email', 'to_user_name', 'to_user_email', 'from_currency_name', 'from_currency_code', 'to_currency_name', 'to_currency_code', 'from_amount', 'to_amount', 'admin_share', 'admin_share_amount', 'base_amount', 'payment_method', 'status'],
                properties: [
                    new OA\Property(property: 'swap_offers_requests_id', type: 'integer',  example: 1),
                    new OA\Property(property: 'from_user_name',          type: 'string',   example: 'Ahmed Khan'),
                    new OA\Property(property: 'from_user_email',         type: 'string',   format: 'email', example: 'ahmed@gmail.com'),
                    new OA\Property(property: 'to_user_name',            type: 'string',   example: 'Sara Ali'),
                    new OA\Property(property: 'to_user_email',           type: 'string',   format: 'email', example: 'sara@gmail.com'),
                    new OA\Property(property: 'from_currency_name',      type: 'string',   example: 'Euro'),
                    new OA\Property(property: 'from_currency_code',      type: 'string',   example: 'EUR'),
                    new OA\Property(property: 'to_currency_name',        type: 'string',   example: 'US Dollar'),
                    new OA\Property(property: 'to_currency_code',        type: 'string',   example: 'USD'),
                    new OA\Property(property: 'from_amount',             type: 'number',   format: 'float', example: 500.00),
                    new OA\Property(property: 'to_amount',               type: 'number',   format: 'float', example: 450.00),
                    new OA\Property(property: 'admin_share',             type: 'number',   format: 'float', example: 2.5),
                    new OA\Property(property: 'admin_share_amount',      type: 'number',   format: 'float', example: 12.50),
                    new OA\Property(property: 'base_amount',             type: 'number',   format: 'float', example: 500.00),
                    new OA\Property(property: 'payment_method',          type: 'string',   example: 'Wallet'),
                    new OA\Property(property: 'status',                  type: 'string',   example: 'completed'),
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
            'swap_offers_requests_id' => ['required', 'integer'],
            'from_user_name'          => ['required', 'string', 'max:150'],
            'from_user_email'         => ['required', 'email'],
            'to_user_name'            => ['required', 'string', 'max:150'],
            'to_user_email'           => ['required', 'email'],
            'from_currency_name'      => ['required', 'string', 'max:100'],
            'from_currency_code'      => ['required', 'string', 'max:10'],
            'to_currency_name'        => ['required', 'string', 'max:100'],
            'to_currency_code'        => ['required', 'string', 'max:10'],
            'from_amount'             => ['required', 'numeric', 'min:0'],
            'to_amount'               => ['required', 'numeric', 'min:0'],
            'admin_share'             => ['required', 'numeric', 'min:0'],
            'admin_share_amount'      => ['required', 'numeric', 'min:0'],
            'base_amount'             => ['required', 'numeric', 'min:0'],
            'payment_method'          => ['required', 'string', 'max:100'],
            'status'                  => ['required', 'string', 'max:100'],
        ]);

        $record = ProductsPurchase::updateOrCreate(
            ['partner_id' => $partner->id, 'swap_offers_requests_id' => $validated['swap_offers_requests_id']],
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
        parameters: [],
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
}
