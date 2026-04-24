<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $fillable = [
        'uuid',
        'customer_id',
        'partner_id',
        'product_id',
        'transaction_number',
        'product_type',
        'cover_duration',
        'cover_start_date',
        'cover_end_date',
        'amount',
        'currency',
        'paid_at',
        'transaction_reference',
        'status',
        'payment_message',
        'stripe_payment_intent',
        'stripe_payment_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount'           => 'decimal:2',
            'paid_at'          => 'datetime',
            'cover_start_date' => 'date',
            'cover_end_date'   => 'date',
            'metadata'         => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment): void {
            if (! $payment->uuid) {
                $payment->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Alias for {@see $paid_at} (legacy attribute name used in admin UI).
     */
    public function getPaymentDateAttribute(): ?CarbonInterface
    {
        return $this->paid_at;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }
}
