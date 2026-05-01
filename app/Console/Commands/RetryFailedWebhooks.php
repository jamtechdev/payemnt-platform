<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RetryWebhookDeliveryJob;
use App\Models\WebhookLog;
use Illuminate\Console\Command;

class RetryFailedWebhooks extends Command
{
    protected $signature = 'app:retry-failed-webhooks';

    protected $description = 'Dispatch retries for failed webhook logs';

    public function handle(): int
    {
        $logs = WebhookLog::query()
            ->where('status', 'failed')
            ->whereNotNull('next_retry_at')
            ->where('next_retry_at', '<=', now())
            ->limit(100)
            ->get();

        foreach ($logs as $log) {
            RetryWebhookDeliveryJob::dispatch($log->id);
        }

        $this->info("Dispatched {$logs->count()} webhook retries.");

        return self::SUCCESS;
    }
}
