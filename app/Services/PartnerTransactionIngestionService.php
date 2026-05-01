<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;
use App\Models\TransactionLog;
use Throwable;

class PartnerTransactionIngestionService
{
    public function ingest(Partner $partner, array $payload): Payment
    {
        try {
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

            $payment = Payment::query()->updateOrCreate(
                [
                    'partner_id' => $partner->id,
                    'transaction_number' => $payload['transaction_number'],
                ],
                [
                    'customer_id' => $customer->id,
                    'product_id' => $product->id,
                    'customer_name' => $payload['customer_name'],
                    'customer_email' => $payload['customer_email'],
                    'phone' => $payload['phone'] ?? null,
                    'policy_number' => $payload['policy_number'] ?? null,
                    'cover_duration' => $payload['cover_duration'],
                    'status' => $payload['status'] ?? Payment::STATUS_PENDING,
                    'notes' => $payload['notes'] ?? null,
                    'kyc_data' => $payload['kyc'] ?? null,
                    'submitted_payload' => $payload,
                    'api_response' => ['status' => 'accepted'],
                    'amount' => $payload['amount'] ?? 0,
                    'currency' => strtoupper((string) ($payload['currency'] ?? 'USD')),
                    'paid_at' => $payload['date_added'] ?? now(),
                ]
            );

            TransactionLog::query()->create([
                'payment_id' => $payment->id,
                'partner_id' => $partner->id,
                'event' => 'transaction_ingested',
                'request_payload' => $payload,
                'response_payload' => ['payment_id' => $payment->id],
                'status_code' => 200,
                'source' => 'partner_api',
                'occurred_at' => now(),
            ]);

            return $payment;
        } catch (Throwable $exception) {
            TransactionLog::query()->create([
                'payment_id' => null,
                'partner_id' => $partner->id,
                'event' => 'transaction_ingest_failed',
                'request_payload' => $payload,
                'response_payload' => null,
                'status_code' => 422,
                'error_message' => $exception->getMessage(),
                'source' => 'partner_api',
                'occurred_at' => now(),
            ]);
            throw $exception;
        }
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
