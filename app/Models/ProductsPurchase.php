<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsPurchase extends Model
{
    protected $fillable = [
        'swap_offers_requests_id',
        'from_user_name', 'from_user_email',
        'to_user_name', 'to_user_email',
        'from_currency_name', 'from_currency_code',
        'to_currency_name', 'to_currency_code',
        'from_amount', 'to_amount',
        'admin_share', 'admin_share_amount',
        'base_amount', 'payment_method', 'status',
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
}
