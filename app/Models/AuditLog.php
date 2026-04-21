<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

class AuditLog extends Model
{
    protected $fillable = [
        'actor_user_id',
        'partner_id',
        'action',
        'entity_type',
        'entity_id',
        'ip_address',
        'user_agent',
        'changes',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public static function record(string $action, mixed $entity = null, array $old = [], array $new = [], ?User $actor = null): void
    {
        /** @var Request|null $request */
        $request = request();

        self::query()->create([
            'actor_user_id' => $actor?->id,
            'partner_id' => null,
            'action' => $action,
            'entity_type' => is_object($entity) ? $entity::class : 'system',
            'entity_id' => is_object($entity) && method_exists($entity, 'getKey') ? $entity->getKey() : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'changes' => ['old' => $old, 'new' => $new],
            'occurred_at' => now(),
        ]);
    }
}
