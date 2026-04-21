<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AuditLog;

class AuditLogRepository
{
    public function create(array $payload): AuditLog
    {
        return AuditLog::query()->create($payload);
    }
}
