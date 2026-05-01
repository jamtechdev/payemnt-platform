<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerRequestIdempotency extends Model
{
    protected $fillable = [
        'partner_id',
        'idempotency_key',
        'endpoint',
        'request_hash',
        'status_code',
        'response_payload',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'response_payload' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
