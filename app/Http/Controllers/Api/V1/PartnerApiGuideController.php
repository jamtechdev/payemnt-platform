<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

class PartnerApiGuideController extends BaseApiController
{
    public function __invoke(): JsonResponse
    {
        return $this->success([
            'title' => 'Insurtech Partner API Guide',
            'version' => 'v1',
            'base_path' => '/api/v1',
            'authentication' => [
                'type' => 'Bearer token',
                'header' => 'Authorization: Bearer {partner_token}',
                'rate_limit' => 'throttle:partner_api',
                'step_1_verify_partner' => [
                    'method' => 'POST',
                    'endpoint' => '/api/v1/verify',
                    'payload' => [
                        'partner_code' => 'SWAP_CIRCLE',
                        'api_key' => 'partner-generated-api-key',
                        'base_url' => 'https://partner.example.com',
                    ],
                ],
            ],
            'integration_flow' => [
                'Admin creates product',
                'Admin assigns product access to partner',
                'Partner fetches product list and schema',
                'Partner sends customer sales to transactions endpoint',
                'Admin monitors transactions and analytics',
            ],
            'endpoints' => [
                'products_list' => [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/partner/products',
                ],
                'product_schema' => [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/partner/products/{uuid}/schema',
                ],
                'create_or_upsert_transaction' => [
                    'method' => 'POST',
                    'endpoint' => '/api/v1/transactions',
                    'idempotency_header' => 'Idempotency-Key: {transaction_number}',
                    'payload' => [
                        'transaction_number' => 'TXN-2026-0001',
                        'customer_name' => 'John Doe',
                        'customer_email' => 'john@example.com',
                        'product_code' => 'NIGERIA_BENEFICIARY_COMMUNITY',
                        'cover_duration' => '12_months',
                        'status' => 'active',
                        'notes' => 'Captured from partner checkout',
                        'date_added' => '2026-04-30 12:00:00',
                    ],
                    'success_response' => [
                        'status' => 'success',
                        'data' => [
                            'transaction_number' => 'TXN-2026-0001',
                            'customer_name' => 'John Doe',
                            'customer_email' => 'john@example.com',
                            'cover_duration' => '12_months',
                            'status' => 'active',
                            'notes' => 'Captured from partner checkout',
                            'created_at' => '2026-04-30T12:00:00+00:00',
                        ],
                    ],
                ],
            ],
            'security_rules' => [
                'guide_price is never exposed in partner endpoints',
                'product must be assigned and enabled for partner',
                'invalid token or inactive partner is rejected',
                'all payloads are validated using Laravel FormRequest',
            ],
        ]);
    }
}
