<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

class PurgeAuditLogs extends Command
{
    protected $signature = 'app:purge-audit-logs {--years=7}';
    protected $description = 'Delete audit logs older than the retention window (default 7 years); compliant records within the window are kept';

    public function handle(): int
    {
        $years = max(1, (int) $this->option('years'));
        $cutoff = now()->subYears($years);

        $deleted = AuditLog::query()->where('created_at', '<', $cutoff)->delete();
        $this->info("Purged {$deleted} audit logs older than {$years} year(s).");

        return self::SUCCESS;
    }
}
