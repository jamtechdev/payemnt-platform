<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Payment extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'uuid',
        'customer_id',
        'partner_id',
        'product_id',
        'transaction_number',
        'customer_name',
        'customer_email',
        'phone',
        'policy_number',
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
        'notes',
        'kyc_data',
        'submitted_payload',
        'api_response',
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
            'kyc_data' => 'array',
            'submitted_payload' => 'array',
            'api_response' => 'array',
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

    public function transactionLogs(): HasMany
    {
        return $this->hasMany(TransactionLog::class, 'payment_id');
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    public function scopeForRange(Builder $query, ?CarbonInterface $from = null, ?CarbonInterface $to = null): Builder
    {
        return $query
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to));
    }
}
