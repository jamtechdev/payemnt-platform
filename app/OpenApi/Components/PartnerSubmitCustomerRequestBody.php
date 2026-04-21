<?php

declare(strict_types=1);

namespace App\OpenApi\Components;

use OpenApi\Attributes as OA;

#[OA\RequestBody(
    request: 'PartnerSubmitCustomer',
    required: true,
    content: new OA\JsonContent(
        required: ['product_id', 'customer_data', 'payment'],
        properties: [
            new OA\Property(
                property: 'partner_id',
                type: 'string',
                example: 'PARTNER_001',
                description: 'Partner code. Must match authenticated partner token.'
            ),
            new OA\Property(property: 'product_id', type: 'string', example: 'PROD_123', description: 'Product code enabled for this partner'),
            new OA\Property(
                property: 'customer_data',
                type: 'object',
                additionalProperties: true,
                description: 'Dynamic fields from product configuration with runtime validation.'
            ),
            new OA\Property(
                property: 'payment',
                type: 'object',
                required: ['amount', 'currency', 'paid_at'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float'),
                    new OA\Property(property: 'currency', type: 'string', example: 'USD'),
                    new OA\Property(
                        property: 'paid_at',
                        type: 'string',
                        description: 'ISO-8601 datetime, e.g. 2026-04-17T12:30:00Z'
                    ),
                    new OA\Property(property: 'transaction_reference', type: 'string', nullable: true),
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'metadata', type: 'object', nullable: true),
                ]
            ),
        ]
    )
)]
final class PartnerSubmitCustomerRequestBody
{
}
