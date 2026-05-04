<?php

declare(strict_types=1);

namespace App\Support;

final class PartnerIntegrationGuide
{
    /**
     * @return array<string, mixed>
     */
    public static function payload(?string $publicBase = null): array
    {
        $publicBase = $publicBase ?? rtrim((string) config('app.url'), '/');

        return [
            'title' => 'Insurtech Partner API Guide',
            'version' => 'v1',
            'base_path' => '/api/v1',
            'public_base_url' => $publicBase,
            'urls' => [
                'partner_api_html_doc' => $publicBase !== '' ? "{$publicBase}/docs/partner-api" : '/docs/partner-api',
                'this_guide' => $publicBase !== '' ? "{$publicBase}/api/v1/partner/guide" : '/api/v1/partner/guide',
                'swagger_ui' => $publicBase !== '' ? "{$publicBase}/api/documentation" : '/api/documentation',
                'verify_partner' => $publicBase !== '' ? "{$publicBase}/api/v1/verify" : '/api/v1/verify',
            ],
            'connection_prerequisites' => [
                'Partner infrastructure can reach public_base_url over HTTPS (HTTP acceptable for local dev only).',
                'Bearer token issued for an active partner via Super Admin → Partners → Generate API Key.',
                'At least one product is assigned to the partner with access enabled.',
                'Partner application maps its internal product identifiers to Insurtech product_code values returned by GET /api/v1/partner/products before calling submit.',
            ],
            'products_creation_and_sharing' => [
                'creation' => [
                    'Super Admin UI: Products → Create (/admin/super-admin/products/create).',
                    'Required on form: name, partner (primary partner), status (active/inactive). Insurtech auto-generates product_code (uppercase slug + suffix) used in all partner URLs.',
                    'Add dynamic fields for KYC/policy questions as needed; saving regenerates api_schema.',
                    'Partners cannot create products with the partner Bearer token: POST /api/v1/partner/products returns 403.',
                ],
                'sharing_to_partner_api' => [
                    'Primary: selecting a partner on product create links that partner with access enabled.',
                    'Additional partners: Partners → open partner → assign products and toggle enabled for each.',
                    'GET /api/v1/partner/products lists active products only when partner_product contains that partner with is_enabled=true (same rule as submit/KYC product resolution).',
                ],
                'admin_rest_note' => 'Full product CRUD for automation is under /api/v1/admin/products with Sanctum user auth (super_admin), not the partner API key.',
            ],
            'admin_setup' => [
                'Create products that partners may distribute (see products_creation_and_sharing in this JSON).',
                'Super Admin: Partners → create or edit a partner; set status active; record partner_code.',
                'Assign products to the partner and enable each assignment. New partners do not see catalog items until this assignment exists.',
                'Generate API Key on the partner; copy the shown token once into the partner app secret store.',
            ],
            'partner_environment' => [
                [
                    'key' => 'INSURETECH_ADMIN_BASE_URL',
                    'description' => 'Public origin of this Insurtech portal; no trailing slash.',
                    'example' => $publicBase !== '' ? $publicBase : 'https://your-insurtech.example.com',
                ],
                [
                    'key' => 'INSURETECH_PARTNER_TOKEN',
                    'description' => 'Bearer token from Generate API Key.',
                    'example' => '(secret — store in env or vault)',
                ],
                [
                    'key' => 'INSURETECH_REQUEST_TIMEOUT',
                    'description' => 'HTTP client timeout in seconds.',
                    'example' => '20',
                ],
            ],
            'config_file_note' => [
                'laravel_example' => 'config/insuretech.php with admin_base_url, partner_token, request_timeout_seconds from env().',
                'runtime_settings_note' => 'You may also load base URL and token from your own database-backed settings if you prefer that over env vars.',
            ],
            'suggested_partner_service' => [
                'summary' => 'Encapsulate all Insurtech HTTP calls in one service class or module in your partner application.',
                'responsibilities' => [
                    'Load base URL, token, and timeout from secure configuration.',
                    'Build one HTTP client: Accept application/json, Authorization Bearer {token}.',
                    'Fail fast if base URL or token is missing.',
                    'Expose testConnection, pullCatalog, submitPolicy, and submitKyc.',
                    'Persist catalog and map local product IDs to Insurtech product_code before submit.',
                ],
                'suggested_methods' => [
                    [
                        'name' => 'testConnection',
                        'http' => 'GET',
                        'path' => '/api/v1/partner/products',
                        'required_headers' => ['Authorization: Bearer {token}', 'Accept: application/json'],
                        'notes' => 'Use as health check; same call as catalog pull.',
                    ],
                    [
                        'name' => 'pullCatalog',
                        'http' => 'GET',
                        'path' => '/api/v1/partner/products',
                        'required_headers' => ['Authorization: Bearer {token}', 'Accept: application/json'],
                        'notes' => 'Parse response data array; upsert local catalog and code mapping table.',
                    ],
                    [
                        'name' => 'submitPolicy',
                        'http' => 'POST',
                        'path' => '/api/v1/products/{product_code}/submit',
                        'required_headers' => ['Authorization: Bearer {token}', 'Idempotency-Key: {unique}', 'Content-Type: application/json'],
                        'notes' => 'Idempotency-Key is mandatory; use a new key per distinct submit attempt.',
                    ],
                    [
                        'name' => 'submitKyc',
                        'http' => 'POST',
                        'path' => '/api/v1/products/{product_code}/transactions/{transaction_number}/kyc',
                        'required_headers' => ['Authorization: Bearer {token}', 'Content-Type: application/json'],
                        'notes' => 'JSON body must include a kyc object.',
                    ],
                ],
            ],
            'connection_validation_checklist' => [
                'Call GET /api/v1/partner/products with Bearer token; expect HTTP 200 and status success in JSON.',
                'HTTP 401: invalid token, wrong token type, or partner inactive — regenerate key or fix partner status.',
                'HTTP 200 with empty data array: no products assigned or enabled for this partner — fix assignments in admin.',
                'Optional: GET /api/v1/verify-token with Bearer returns partner metadata when token is valid.',
            ],
            'swagger' => [
                'ui_path' => '/api/documentation',
                'regenerate_command' => 'php artisan l5-swagger:generate',
                'generated_document' => 'storage/api-docs/api-docs.json',
                'note' => 'Run regenerate_command from the admin-portal project root after changing OpenAPI PHP attributes.',
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
                    'title' => 'Admin portal: create the connection',
                    'actions' => [
                        'Create products partners may sell.',
                        'Super Admin: Partners → create partner (active); note partner_code.',
                        'Assign and enable product access for that partner.',
                        'Generate API Key; copy Bearer token once into partner secret store.',
                    ],
                ],
                [
                    'step' => 2,
                    'title' => 'Partner app: configuration',
                    'actions' => [
                        'Set INSURETECH_ADMIN_BASE_URL (or equivalent) to public_base_url — example: ' . ($publicBase !== '' ? $publicBase : 'https://your-insurtech-portal.example.com') . '.',
                        'Set INSURETECH_PARTNER_TOKEN to the copied Bearer token.',
                        'Set request timeout (e.g. INSURETECH_REQUEST_TIMEOUT).',
                    ],
                ],
                [
                    'step' => 3,
                    'title' => 'Partner app: implement integration service',
                    'actions' => [
                        'Add a dedicated service class wrapping HTTP calls (see suggested_partner_service in this JSON).',
                        'Use one client factory: baseUrl + Bearer + Accept application/json + timeout.',
                        'Implement testConnection and pullCatalog using GET /api/v1/partner/products.',
                        'Implement submitPolicy and submitKyc for the recommended sale flow.',
                    ],
                ],
                [
                    'step' => 4,
                    'title' => 'Validate the connection',
                    'actions' => [
                        'Run testConnection; expect HTTP 200 and non-error JSON.',
                        'If 401, fix token or partner status; if 200 with empty catalog, fix product assignments.',
                    ],
                ],
                [
                    'step' => 5,
                    'title' => 'Optional: POST /api/v1/verify',
                    'actions' => [
                        'Send partner_code, api_key (plaintext at generation), base_url — updates connected metadata; does not return Bearer token.',
                    ],
                ],
                [
                    'step' => 6,
                    'title' => 'Sync catalog',
                    'actions' => [
                        'GET /api/v1/partner/products — guide_price is never returned; map product_code locally.',
                    ],
                ],
                [
                    'step' => 7,
                    'title' => 'Record a sale (recommended flow)',
                    'actions' => [
                        'POST /api/v1/products/{product_code}/submit with Idempotency-Key header (required).',
                        'POST /api/v1/products/{product_code}/transactions/{transaction_number}/kyc with JSON body containing kyc object.',
                    ],
                ],
            ],
            'reference_implementation' => [
                'name' => 'Typical partner backend',
                'summary' => 'Any stack can integrate: keep INSURETECH_ADMIN_BASE_URL (or equivalent) and INSURETECH_PARTNER_TOKEN in secure configuration, then call the REST paths below.',
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
                'Admin monitors transactions and analytics',
            ],
            'endpoints' => [
                'machine_readable_guide' => [
                    'method' => 'GET',
                    'endpoint' => '/api/v1/partner/guide',
                    'auth' => 'none',
                ],
                'partner_api_html_doc' => [
                    'method' => 'GET',
                    'endpoint' => '/docs/partner-api',
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
            ],
            'security_rules' => [
                'guide_price is never exposed in partner endpoints',
                'product must be assigned and enabled for partner',
                'invalid token or inactive partner is rejected',
                'all payloads are validated using Laravel FormRequest',
            ],
        ];
    }
}
