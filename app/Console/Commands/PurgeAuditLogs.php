<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class PurgeAuditLogs extends Command
{
    protected $signature = 'app:purge-audit-logs {--years=7}';
    protected $description = 'Purge audit logs older than retention period';

    public function handle(): int
    {
        $years = max(1, (int) $this->option('years'));
        $cutoff = now()->subYears($years);

        $deleted = AuditLog::query()->where('created_at', '<', $cutoff)->delete();
        $this->info("Purged {$deleted} audit logs older than {$years} year(s).");

        return self::SUCCESS;
    }
}
