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
#[OA\Tag(name: 'Products',       description: 'Product management APIs')]
#[OA\Tag(name: 'Customer',       description: 'Customer registration & management')]
#[OA\Tag(name: 'Transactions',   description: 'Transaction APIs')]
#[OA\Tag(name: 'Swap Offers',    description: 'Swap offer APIs')]
#[OA\Tag(name: 'Occupations',    description: 'Occupation lookup APIs')]
#[OA\Tag(name: 'Relationships',  description: 'Relationship lookup APIs')]
#[OA\Tag(name: 'Task Types',     description: 'Task type lookup APIs')]
final class OpenApiSpec
{
}
