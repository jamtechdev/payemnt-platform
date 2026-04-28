<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductsPurchase extends Model
{
    protected $fillable = [
        'partner_id', 'customer_email', 'product_code', 'product_type',
        'cover_duration', 'cover_start_date', 'cover_end_date',
        'payment_status', 'transaction_number', 'date_added',
    ];

    protected function casts(): array
    {
        return [
            'cover_start_date' => 'date',
            'cover_end_date'   => 'date',
            'date_added'       => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
