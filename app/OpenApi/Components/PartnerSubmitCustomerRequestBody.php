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
                example: 'SWAP_CIRCLE',
                description: 'Partner code. Must match authenticated partner token.'
            ),
            new OA\Property(property: 'product_id', type: 'string', example: 'NIGERIA_BENEFICIARY_COMMUNITY', description: 'Product code enabled for this partner'),
            new OA\Property(
                property: 'customer_data',
                type: 'object',
                required: ['beneficiary_first_name', 'beneficiary_surname', 'beneficiary_date_of_birth', 'beneficiary_gender', 'beneficiary_address', 'cover_start_date', 'cover_duration'],
                properties: [
                    new OA\Property(property: 'beneficiary_first_name', type: 'string', example: 'Amina'),
                    new OA\Property(property: 'beneficiary_surname', type: 'string', example: 'Okafor'),
                    new OA\Property(property: 'beneficiary_date_of_birth', type: 'string', example: '1994-04-15'),
                    new OA\Property(property: 'beneficiary_gender', type: 'string', example: 'female'),
                    new OA\Property(property: 'beneficiary_address', type: 'string', example: 'Lekki, Lagos'),
                    new OA\Property(property: 'cover_start_date', type: 'string', example: '2026-05-01'),
                    new OA\Property(property: 'cover_duration', type: 'string', example: 'monthly'),
                ],
                description: 'Beneficiary fields required by the product template.'
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
