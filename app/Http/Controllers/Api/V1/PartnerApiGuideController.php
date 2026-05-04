<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PartnerApiGuideController extends BaseApiController
{
    #[OA\Get(
        path: '/api/v1/partner/guide',
        operationId: 'partnerApiGuide',
        summary: 'Public JSON integration guide',
        description: 'No authentication. Returns `data` with steps, endpoint paths, `public_base_url` (from APP_URL), and Swap Circle reference notes.',
        tags: ['Guide'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success envelope',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'object', description: 'Guide payload'),
                    ]
                )
            ),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        $publicBase = rtrim((string) config('app.url'), '/');

        return $this->success([
            'title' => 'Insurtech Partner API Guide',
            'version' => 'v1',
            'base_path' => '/api/v1',
            'public_base_url' => $publicBase,
            'urls' => [
                'this_guide' => $publicBase !== '' ? "{$publicBase}/api/v1/partner/guide" : '/api/v1/partner/guide',
                'swagger_ui' => $publicBase !== '' ? "{$publicBase}/api/documentation" : '/api/documentation',
                'verify_partner' => $publicBase !== '' ? "{$publicBase}/api/v1/verify" : '/api/v1/verify',
            ],
            'authentication' => [
                'type' => 'Bearer token (Laravel Sanctum personal access token issued to the Partner record)',
                'header' => 'Authorization: Bearer {partner_token}',
                'rate_limit' => 'throttle:partner_api',
                'how_to_obtain_token' => 'Super Admin: Partners → open partner → Generate API Key. Copy once; store as partner secret on your platform.',
                'optional_registration_call' => [
                    'method' => 'POST',
                    'path' => '/api/v1/verify',
                    'full_url_example' => $publicBase !== '' ? "{$publicBase}/api/v1/verify" : null,
                    'payload' => [
                        'partner_code' => 'YOUR_PARTNER_CODE',
                        'api_key' => 'plaintext-key-shown-once-when-generated',
                        'base_url' => 'https://your-partner-app.example.com',
                    ],
                    'note' => 'Updates connected_base_url on the partner; does not return the Bearer token.',
                ],
            ],
            'integration_steps' => [
                [
                    'step' => 1,
                    'title' => 'Admin portal onboarding',
                    'actions' => [
                        'Create products and set partner-eligible catalog.',
                        'Super Admin creates a Partner (partner_code, name, status active).',
                        'Assign product access to that partner.',
                        'Generate API Key for the partner and copy the Bearer token to the partner system.',
                    ],
                ],
                [
                    'step' => 2,
                    'title' => 'Configure partner application',
                    'actions' => [
                        'Set base URL to this portal public origin (example: ' . ($publicBase !== '' ? $publicBase : 'https://your-insurtech-portal.example.com') . ').',
                        'Store token securely (environment variable or encrypted settings).',
                        'All authenticated calls: Authorization: Bearer {token}, Accept: application/json.',
                    ],
                ],
                [
                    'step' => 3,
                    'title' => 'Sync catalog',
                    'actions' => [
                        'GET /api/v1/partner/products — list products available to this partner (guide_price is never returned).',
                    ],
                ],
                [
                    'step' => 4,
                    'title' => 'Record a sale (recommended — same as Swap Circle)',
                    'actions' => [
                        'POST /api/v1/products/{product_code}/submit with header Idempotency-Key (required, unique per logical submit).',
                        'POST /api/v1/products/{product_code}/transactions/{transaction_number}/kyc with body { "kyc": { ... } }.',
                    ],
                ],
                [
                    'step' => 5,
                    'title' => 'Health check',
                    'actions' => [
                        'GET /api/v1/partner/products with Bearer token — 200 and JSON body means credentials and assignments are valid.',
                    ],
                ],
            ],
            'reference_implementation' => [
                'name' => 'Swap Circle (swap-circle)',
                'summary' => 'Laravel app uses InsuretechSyncService: runtime settings insuretech_admin_base_url + insuretech_partner_token (or INSURETECH_ADMIN_BASE_URL / INSURETECH_PARTNER_TOKEN in .env).',
                'calls' => [
                    'test_connection_and_pull' => 'GET {ADMIN_BASE_URL}/api/v1/partner/products',
                    'submit_policy' => 'POST {ADMIN_BASE_URL}/api/v1/products/{product_code}/submit',
                    'submit_kyc' => 'POST {ADMIN_BASE_URL}/api/v1/products/{product_code}/transactions/{transaction_number}/kyc',
                ],
            ],
            'integration_flow' => [
                'Admin creates product',
                'Admin assigns product access to partner',
                'Partner fetches product list (GET /api/v1/partner/products)',
                'Partner submits policy (POST /api/v1/products/{product_code}/submit) then KYC if applicable',
                'Optional: alternate single-shot ingest via POST /api/v1/transactions',
                'Admin monitors transactions and analytics',
            ],
            'endpoints' => [
                'machine_readable_guide' => [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/partner/guide',
                    'auth' => 'none',
                ],
                'products_list' => [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/partner/products',
                    'auth' => 'Bearer partner token',
                ],
                'verify_token' => [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/verify-token',
                    'auth' => 'Bearer partner token',
                ],
                'submit_policy' => [
                    'method' => 'POST',
                    'endpoint' => '/api/v1/products/{product_code}/submit',
                    'auth' => 'Bearer partner token',
                    'idempotency_header' => 'Idempotency-Key: {unique_key}',
                    'payload' => [
                        'transaction_number' => 'TXN-2026-0001',
                        'customer_name' => 'John Doe',
                        'customer_email' => 'john@example.com',
                        'cover_duration' => '12_months',
                        'status' => 'active',
                        'phone' => '+2348000000000',
                        'notes' => 'optional',
                        'amount' => 1000.0,
                        'currency' => 'NGN',
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
                'submit_kyc' => [
                    'method' => 'POST',
                    'endpoint' => '/api/v1/products/{product_code}/transactions/{transaction_number}/kyc',
                    'auth' => 'Bearer partner token',
                    'body' => ['kyc' => ['id_type' => 'national_id', 'id_number' => 'ABC12345']],
                ],
                'create_or_upsert_transaction' => [
                    'method' => 'POST',
                    'endpoint' => '/api/v1/transactions',
                    'auth' => 'Bearer partner token',
                    'note' => 'Alternative to submit+kyc; useful for simpler partner integrations. Optional Idempotency-Key must match transaction_number when sent.',
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
