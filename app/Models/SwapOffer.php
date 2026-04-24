<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SwapOffer extends Model
{
    protected $fillable = [
        'uuid',
        'partner_id',
        'customer_id',
        'customer_email',
        'from_currency_code',
        'to_currency_code',
        'from_amount',
        'to_amount',
        'admin_share',
        'admin_share_amount',
        'exchange_rate',
        'base_amount',
        'expiry_date_time',
        'status',
        'date_added',
    ];

    protected function casts(): array
    {
        return [
            'from_amount'        => 'decimal:2',
            'to_amount'          => 'decimal:2',
            'admin_share'        => 'decimal:2',
            'admin_share_amount' => 'decimal:2',
            'exchange_rate'      => 'decimal:8',
            'base_amount'        => 'decimal:2',
            'expiry_date_time'   => 'datetime',
            'date_added'         => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SwapOffer $offer): void {
            if (! $offer->uuid) {
                $offer->uuid = (string) Str::uuid();
            }
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
