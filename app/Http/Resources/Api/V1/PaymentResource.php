<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->uuid,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'paid_at' => optional($this->paid_at)->toIso8601String(),
            'status' => $this->status,
            'transaction_reference' => $this->transaction_reference,
            'metadata' => $this->metadata ?? [],
        ];
    }
}
