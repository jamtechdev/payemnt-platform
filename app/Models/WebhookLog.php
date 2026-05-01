<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WebhookLog extends Model
{
    protected $fillable = [
        'uuid',
        'partner_id',
        'payment_id',
        'event',
        'target_url',
        'payload',
        'status_code',
        'attempt',
        'status',
        'response_body',
        'error_message',
        'next_retry_at',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'next_retry_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WebhookLog $log): void {
            if (! $log->uuid) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }
}
