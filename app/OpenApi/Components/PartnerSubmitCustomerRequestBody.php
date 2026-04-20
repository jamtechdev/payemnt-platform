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
                type: 'integer',
                nullable: true,
                description: 'Optional. When omitted, the authenticated partner id is used.'
            ),
            new OA\Property(property: 'product_id', type: 'integer', description: 'Product id enabled for this partner'),
            new OA\Property(
                property: 'customer_data',
                type: 'object',
                additionalProperties: true,
                description: 'Dynamic fields from product definition (e.g. beneficiary_first_name, beneficiary_surname, beneficiary_date_of_birth, beneficiary_gender, beneficiary_address, cover_start_date, cover_duration monthly/annual, first_name, last_name, email). beneficiary_age is auto-calculated from beneficiary_date_of_birth.'
            ),
            new OA\Property(
                property: 'payment',
                type: 'object',
                required: ['amount', 'currency', 'payment_date', 'transaction_reference'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float'),
                    new OA\Property(property: 'currency', type: 'string', example: 'USD'),
                    new OA\Property(
                        property: 'payment_date',
                        type: 'string',
                        description: 'ISO-8601 datetime, e.g. 2026-04-17T12:30:00Z'
                    ),
                    new OA\Property(property: 'transaction_reference', type: 'string'),
                    new OA\Property(property: 'payment_status', type: 'string', example: 'success'),
                ]
            ),
        ]
    )
)]
final class PartnerSubmitCustomerRequestBody
{
}
