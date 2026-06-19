<?php

declare(strict_types=1);

namespace App\Support;

final class PartnerApiContract
{
    /**
     * What every partner must send on sale + optional KYC — shown in admin product UI.
     *
     * @return array<string, mixed>
     */
    public static function salePayload(): array
    {
        return [
            'summary' => 'When a partner sells a product, they POST to Insurtech. You define extra fields in admin; partners read them via GET /api/v1/products/{product_code}/fields.',
            'submit_endpoint' => 'POST /api/v1/products/{product_code}/submit',
            'kyc_endpoint' => 'POST /api/v1/products/{product_code}/transactions/{transaction_number}/kyc',
            'required_on_every_sale' => [
                ['field' => 'transaction_number', 'type' => 'string', 'notes' => 'Partner\'s unique sale reference'],
                ['field' => 'customer_name', 'type' => 'string', 'notes' => 'Buyer full name'],
                ['field' => 'customer_email', 'type' => 'email', 'notes' => 'Buyer email'],
                ['field' => 'cover_duration', 'type' => 'string', 'notes' => 'e.g. 30, 90, 365 or 12_months — match product cover options'],
            ],
            'optional_on_sale' => [
                ['field' => 'phone', 'type' => 'string'],
                ['field' => 'status', 'type' => 'active|pending|suspended'],
                ['field' => 'policy_number', 'type' => 'string'],
                ['field' => 'amount', 'type' => 'number'],
                ['field' => 'currency', 'type' => 'string (3-letter)'],
                ['field' => 'kyc', 'type' => 'object', 'notes' => 'May be sent on submit or via KYC endpoint after'],
            ],
            'kyc_object_example' => [
                'id_type' => 'national_id | passport | phone',
                'id_number' => 'ID or phone value',
                'first_name' => 'string',
                'last_name' => 'string',
                'dob' => 'YYYY-MM-DD',
                'address' => 'string',
            ],
            'headers' => [
                'Authorization' => 'Bearer {partner_api_key}',
                'Idempotency-Key' => 'Required on submit — unique per attempt',
                'Content-Type' => 'application/json',
            ],
        ];
    }
}
