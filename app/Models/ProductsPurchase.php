<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductsPurchase extends Model
{
    protected $fillable = [
        'swap_offers_requests_id', 'from_users_customers_id', 'to_users_customers_id',
        'from_system_currencies_id', 'to_system_currencies_id', 'from_amount', 'to_amount',
        'admin_share', 'admin_share_amount', 'system_currencies_id', 'base_amount',
        'payment_method_id', 'status',
    ];

    protected function casts(): array
    {
        return [
            'from_amount'        => 'decimal:2',
            'to_amount'          => 'decimal:2',
            'admin_share'        => 'decimal:2',
            'admin_share_amount' => 'decimal:2',
            'base_amount'        => 'decimal:2',
        ];
    }

    public function swapOffer(): BelongsTo
    {
        return $this->belongsTo(SwapOffer::class, 'swap_offers_requests_id');
    }

    public function fromCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'from_users_customers_id');
    }

    public function toCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'to_users_customers_id');
    }
}
