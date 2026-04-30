<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;

class PartnerTransactionIngestionService
{
    public function ingest(Partner $partner, array $payload): Payment
    {
        $product = Product::query()
            ->where('product_code', $payload['product_code'])
            ->where(function ($query) use ($partner): void {
                $query->where('partner_id', $partner->id)
                    ->orWhereHas('partners', fn ($partnerQuery) => $partnerQuery
                        ->where('partners.id', $partner->id)
                        ->where('partner_product.is_enabled', true));
            })
            ->firstOrFail();

        $customer = Customer::query()->firstOrCreate(
            [
                'partner_id' => $partner->id,
                'email' => $payload['customer_email'],
            ],
            [
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'product_id' => $product->id,
                'first_name' => $this->extractFirstName($payload['customer_name']),
                'last_name' => $this->extractLastName($payload['customer_name']),
                'start_date' => now()->toDateString(),
                'cover_duration_days' => 30,
                'customer_since' => now()->toDateString(),
                'status' => 'Active',
            ]
        );

        if ((int) $customer->product_id !== (int) $product->id) {
            $customer->update(['product_id' => $product->id]);
        }

        return Payment::query()->updateOrCreate(
            [
                'partner_id' => $partner->id,
                'transaction_number' => $payload['transaction_number'],
            ],
            [
                'customer_id' => $customer->id,
                'product_id' => $product->id,
                'customer_name' => $payload['customer_name'],
                'customer_email' => $payload['customer_email'],
                'cover_duration' => $payload['cover_duration'],
                'status' => $payload['status'] ?? 'pending',
                'notes' => $payload['notes'] ?? null,
                'amount' => $payload['amount'] ?? 0,
                'currency' => strtoupper((string) ($payload['currency'] ?? 'USD')),
                'paid_at' => $payload['date_added'] ?? now(),
            ]
        );
    }

    private function extractFirstName(string $customerName): string
    {
        return trim(explode(' ', $customerName)[0]) ?: 'Customer';
    }

    private function extractLastName(string $customerName): string
    {
        $parts = preg_split('/\s+/', trim($customerName)) ?: [];

        if (count($parts) <= 1) {
            return 'Unknown';
        }

        array_shift($parts);

        return trim(implode(' ', $parts));
    }
}
