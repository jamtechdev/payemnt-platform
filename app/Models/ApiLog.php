<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiLog extends Model
{
    protected $fillable = [
        'uuid',
        'partner_id',
        'method',
        'path',
        'endpoint_group',
        'request_body',
        'response_body',
        'status_code',
        'response_time_ms',
        'ip_address',
        'user_agent',
        'source',
        'correlation_id',
        'requested_at',
    ];

    protected function casts(): array
    {
        return [
            'request_body' => 'array',
            'response_body' => 'array',
            'requested_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ApiLog $log): void {
            if (! $log->uuid) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
