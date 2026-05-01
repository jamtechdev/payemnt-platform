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
#[OA\Tag(name: 'Connect',                   description: 'Partner connection & verification APIs')]
#[OA\Tag(name: 'Connect Categories',         description: 'Connect category management APIs')]
#[OA\Tag(name: 'Connect Articles',           description: 'Connect article management APIs')]
#[OA\Tag(name: 'FAQs',                       description: 'FAQ management APIs')]
#[OA\Tag(name: 'Rate APIs',                  description: 'Rate API management')]
#[OA\Tag(name: 'Products',                   description: 'Product management APIs')]
#[OA\Tag(name: 'Customer',                  description: 'Customer registration & management')]
#[OA\Tag(name: 'Transactions',              description: 'Transaction APIs')]
#[OA\Tag(name: 'Distribution',              description: 'Product code based policy submission APIs')]
#[OA\Tag(name: 'Webhooks',                  description: 'Webhook callback and delivery APIs')]
#[OA\Tag(name: 'Swap Offers',               description: 'Swap offer APIs')]
#[OA\Tag(name: 'Occupations',               description: 'Occupation lookup APIs')]
#[OA\Tag(name: 'Relationships',             description: 'Relationship lookup APIs')]
#[OA\Tag(name: 'Task Types',                description: 'Task type lookup APIs')]
#[OA\Tag(name: 'Referral Usages',           description: 'Referral usage APIs')]
#[OA\Tag(name: 'Products Purchases',        description: 'Product purchase APIs')]
#[OA\Tag(name: 'Products Purchases Claims', description: 'Product purchase claim APIs')]
#[OA\Tag(name: 'System Currencies',         description: 'System currency APIs')]
final class OpenApiSpec
{
}
