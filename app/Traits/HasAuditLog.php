<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\AuditLog;

trait HasAuditLog
{
    public static function bootHasAuditLog(): void
    {
        static::created(function ($model): void {
            AuditLog::record('created', $model, [], $model->getAttributes(), auth()->user());
        });

        static::updated(function ($model): void {
            AuditLog::record('updated', $model, $model->getOriginal(), $model->getDirty(), auth()->user());
        });

        static::deleted(function ($model): void {
            AuditLog::record('deleted', $model, $model->getAttributes(), [], auth()->user());
        });
    }
}
