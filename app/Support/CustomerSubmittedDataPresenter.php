<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Support\Collection;

final class CustomerSubmittedDataPresenter
{
    /**
     * @return array<string, scalar|null>
     */
    public static function forCustomer(Customer $customer): array
    {
        $rows = [];
        $fieldLabels = self::productFieldLabels($customer);

        self::mergeRows($rows, self::flattenAssoc($customer->customer_data ?? [], ''), $fieldLabels);

        $latestPayment = $customer->relationLoaded('payments')
            ? $customer->payments->sortByDesc(fn (Payment $payment) => $payment->paid_at)->first()
            : $customer->payments()->latest('paid_at')->first();

        if ($latestPayment) {
            self::mergeRows($rows, self::flattenAssoc($latestPayment->kyc_data ?? [], 'kyc'), $fieldLabels);

            $payload = is_array($latestPayment->submitted_payload) ? $latestPayment->submitted_payload : [];
            $skip = [
                'transaction_number', 'customer_name', 'customer_email', 'phone',
                'product_code', 'cover_duration', 'status', 'kyc', 'notes',
                'amount', 'currency', 'date_added', 'policy_number', 'product',
            ];

            foreach ($payload as $key => $value) {
                if (in_array($key, $skip, true) || $value === null || $value === '') {
                    continue;
                }
                if (is_scalar($value)) {
                    self::mergeRows($rows, [$key => $value], $fieldLabels);
                }
            }

            if (is_array($payload['kyc'] ?? null)) {
                self::mergeRows($rows, self::flattenAssoc($payload['kyc'], 'kyc'), $fieldLabels);
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, scalar|null>  $rows
     * @param  array<string, scalar|null>  $incoming
     */
    private static function mergeRows(array &$rows, array $incoming, Collection $fieldLabels): void
    {
        foreach ($incoming as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $fieldKey = str_starts_with($key, 'kyc_') ? substr($key, 4) : $key;
            $label = $fieldLabels->get($fieldKey)?->label
                ?? $fieldLabels->get($key)?->label
                ?? self::humanizeKey($key);

            $rows[$label] = $value;
        }
    }

    /**
     * @return array<string, scalar|null>
     */
    private static function flattenAssoc(array $data, string $prefix): array
    {
        $flat = [];

        foreach ($data as $key => $value) {
            if (in_array($key, ['policy_notes'], true)) {
                continue;
            }

            $fullKey = $prefix !== '' ? "{$prefix}_{$key}" : (string) $key;

            if (is_array($value)) {
                foreach (self::flattenAssoc($value, $fullKey) as $nestedKey => $nestedValue) {
                    $flat[$nestedKey] = $nestedValue;
                }
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $flat[$fullKey] = $value;
            }
        }

        return $flat;
    }

    private static function productFieldLabels(Customer $customer): Collection
    {
        $product = $customer->relationLoaded('product')
            ? $customer->product
            : $customer->product()->with('fields')->first();

        if (! $product) {
            return collect();
        }

        if (! $product->relationLoaded('fields')) {
            $product->load('fields');
        }

        return $product->fields->keyBy('field_key');
    }

    private static function humanizeKey(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }
}
