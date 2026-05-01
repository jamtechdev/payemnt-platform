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
                'Partner fetches product list',
                'Partner submits customer sale and KYC through product distribution endpoints',
                'Admin monitors transactions and analytics',
            ],
            'endpoints' => [
                'products_list' => [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/partner/products',
                ],
                'verify_token' => [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/verify-token',
                ],
                'submit_policy' => [
                    'method' => 'POST',
                    'endpoint' => '/api/v1/products/{product_code}/submit',
                    'idempotency_header' => 'Idempotency-Key: {unique_key}',
                    'payload' => [
                        'transaction_number' => 'TXN-2026-0001',
                        'customer_name' => 'John Doe',
                        'customer_email' => 'john@example.com',
                        'cover_duration' => '12_months',
                        'status' => 'active',
                        'kyc' => [
                            'id_type' => 'national_id',
                            'id_number' => 'ABC12345',
                        ],
                    ],
                    'success_response' => [
                        'status' => 'success',
                        'data' => [
                            'transaction_number' => 'TXN-2026-0001',
                            'status' => 'active',
                            'policy_number' => 'POL-1001',
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
