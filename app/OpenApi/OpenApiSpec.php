<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '2.0.0',
    title: 'Partner API Platform',
    description: 'Partner integration API for external platforms. Use partner Bearer token on /api/v1/customers and /api/v1/purchase.'
)]
#[OA\Server(url: '/', description: 'Application server (paths already include /api/v1 prefix)')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum token',
    description: 'Send header: Authorization: Bearer {token}'
)]
#[OA\Tag(name: 'Auth', description: 'Admin login and logout for portal access')]
#[OA\Tag(name: 'Customers', description: 'Partner ingestion APIs for customer and purchase sync')]
final class OpenApiSpec
{
}
