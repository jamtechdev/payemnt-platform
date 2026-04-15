<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.2.0',
    title: 'Admin Portal API',
    description: 'Authentication header convention: Authorization: Bearer {sanctum_token}. All secured endpoints use Bearer token in Authorization header.'
)]
#[OA\Server(url: '/', description: 'Application server (paths already include /api/v1 prefix)')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum token',
    description: 'Send header: Authorization: Bearer {token}'
)]
#[OA\Tag(name: 'Auth', description: 'Login, token issue, and logout endpoints')]
#[OA\Tag(name: 'Dashboard', description: 'Role-based dashboard and platform overview')]
#[OA\Tag(name: 'Customers', description: 'Customer submission, search, and detail APIs')]
#[OA\Tag(name: 'Payments', description: 'Payment recording and payment views')]
#[OA\Tag(name: 'Products', description: 'Product configuration and field definitions')]
#[OA\Tag(name: 'Partners', description: 'Partner onboarding and partner-product access')]
#[OA\Tag(name: 'Reports', description: 'Acquisition, revenue, and analytics reports')]
#[OA\Tag(name: 'Users', description: 'User management, roles, and access control')]
#[OA\Tag(name: 'Audit Logs', description: 'System activity and audit trail logs')]
#[OA\Tag(name: 'Settings', description: 'Platform and system settings')]
#[OA\Tag(name: 'Analytics', description: 'Partner API usage analytics')]
final class OpenApiSpec
{
}
