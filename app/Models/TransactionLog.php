<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TransactionLog extends Model
{
    protected $fillable = [
        'uuid',
        'payment_id',
        'partner_id',
        'event',
        'request_payload',
        'response_payload',
        'status_code',
        'error_message',
        'source',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TransactionLog $log): void {
            if (! $log->uuid) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
