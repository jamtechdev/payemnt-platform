<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'transaction_number' => $this->transaction_number,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'cover_duration' => $this->cover_duration,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
