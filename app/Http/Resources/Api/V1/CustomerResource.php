<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isRecon = $user && method_exists($user, 'hasRole') && $user->hasRole('reconciliation_admin');

        return [
            'id' => $this->uuid,
            'partner_id' => $this->partner?->partner_code,
            'product_id' => $this->product?->product_code,
            'first_name' => $isRecon ? null : $this->first_name,
            'last_name' => $isRecon ? null : $this->last_name,
            'email' => $isRecon ? null : $this->email,
            'phone' => $isRecon ? null : $this->phone,
            'status' => $this->status,
            'customer_since' => optional($this->customer_since)->toDateString(),
            'start_date' => optional($this->start_date)->toDateString(),
            'cover_end_date' => optional($this->cover_end_date)->toDateString(),
            'last_payment_date' => optional($this->last_payment_date)->toIso8601String(),
            'payment_count' => $this->whenCounted('payments'),
        ];
    }
}
