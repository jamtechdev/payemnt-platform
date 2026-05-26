<?php

declare(strict_types=1);

namespace App\Services;

class CurrencyService
{
    // Hardcoded rates for demo purposes. In a real app, these would come from an API or DB.
    private array $rates = [
        'USD' => 1.0,
        'EUR' => 0.92,
        'GBP' => 0.79,
        'INR' => 83.33,
        'NGN' => 1500.0,
        'KES' => 131.0,
        'GHS' => 14.2,
        'ZAR' => 18.8,
        'AED' => 3.67,
        'CAD' => 1.36,
        'AUD' => 1.51,
        'JPY' => 156.0,
    ];

    public function convert(float $amount, string $from, string $to): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return $amount;
        }

        if (! isset($this->rates[$from]) || ! isset($this->rates[$to])) {
            return $amount; // Fallback to original if rate missing
        }

        $usdAmount = $amount / $this->rates[$from];
        return $usdAmount * $this->rates[$to];
    }

    public function getRates(): array
    {
        return $this->rates;
    }
}
