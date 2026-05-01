<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '3.0.0',
    title: 'InsurTech Partner Distribution API',
    description: 'Partner product distribution, customer/kyc submission, transaction and webhook APIs.'
)]
#[OA\Server(url: '/', description: 'Application server (paths already include /api/v1 prefix)')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum token',
    description: 'Send header: Authorization: Bearer {token}'
)]
#[OA\Tag(name: 'Connect',      description: 'Partner connection & verification APIs')]
#[OA\Tag(name: 'Products',     description: 'Product sync APIs')]
#[OA\Tag(name: 'Transactions', description: 'Transaction sync APIs')]
final class OpenApiSpec
{
}
