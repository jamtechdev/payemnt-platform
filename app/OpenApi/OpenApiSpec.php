<?php

declare(strict_types=1);

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '2.0.0',
    title: 'Partner API Platform',
    description: 'Admin API for product management.'
)]
#[OA\Server(url: '/', description: 'Application server (paths already include /api/v1 prefix)')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum token',
    description: 'Send header: Authorization: Bearer {token}'
)]
#[OA\Tag(name: 'Products', description: 'Product management APIs')]
#[OA\Tag(name: 'Connect', description: 'Partner connection & verification APIs')]
#[OA\Tag(name: 'Connect Categories', description: 'Connect category management APIs')]
#[OA\Tag(name: 'Connect Articles', description: 'Connect article management APIs')]
#[OA\Tag(name: 'FAQs', description: 'FAQ management APIs')]
final class OpenApiSpec
{
}
