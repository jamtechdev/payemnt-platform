<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\AuditLogRepository;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function __construct(private readonly AuditLogRepository $auditLogRepository)
    {
    }

    public function record(string $action, ?Model $entity = null, array $changes = [], ?User $actor = null): void
    {
        $request = request();
        $this->auditLogRepository->create([
            'actor_user_id' => $actor?->id,
            'partner_id' => null,
            'action' => $action,
            'entity_type' => $entity ? $entity::class : 'system',
            'entity_id' => $entity?->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'changes' => $changes,
            'occurred_at' => now(),
        ]);
    }
}
