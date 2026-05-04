<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '3.1.0',
    title: 'InsurTech Partner & Distribution API',
    description: 'Partner APIs: catalog (GET /api/v1/partner/products), distribution (POST submit + KYC, update, cancel), transactions, customers. Bearer token = Sanctum key from Partners → Generate API Key. Public: GET /api/v1/partner/guide, GET /docs/partner-api, POST /api/v1/verify.'
)]
#[OA\Server(url: '/', description: 'Same host as the admin portal; paths are absolute from web root (e.g. /api/v1/...).')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum personal access token (Partner)',
    description: 'Header: `Authorization: Bearer {token}` — token from Partners → Generate API Key.'
)]
#[OA\Tag(name: 'Guide',          description: 'Public machine-readable integration guide (no Bearer token).')]
#[OA\Tag(name: 'Connect',        description: 'Partner handshake / verification (mostly unauthenticated).')]
#[OA\Tag(name: 'Products',       description: 'Partner product catalog (Bearer required).')]
#[OA\Tag(name: 'Distribution',   description: 'Submit policy, KYC, update, cancel (Bearer required).')]
#[OA\Tag(name: 'Transactions', description: 'Bulk transaction ingest and partner transaction utilities (Bearer required).')]
#[OA\Tag(name: 'Customer',       description: 'Partner-scoped customer register / update / purge (Bearer required).')]
final class OpenApiSpec
{
}
