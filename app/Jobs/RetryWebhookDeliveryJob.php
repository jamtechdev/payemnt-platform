<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class RetryWebhookDeliveryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $webhookLogId)
    {
    }

    public function handle(): void
    {
        $log = WebhookLog::query()->with('partner')->find($this->webhookLogId);
        if (! $log || $log->status === 'sent') {
            return;
        }

        try {
            $signature = hash_hmac('sha256', json_encode($log->payload, JSON_UNESCAPED_UNICODE) ?: '', (string) ($log->partner?->webhook_secret ?? ''));
            $response = Http::timeout(15)
                ->withHeaders(['X-Webhook-Signature' => $signature])
                ->post($log->target_url, $log->payload);

            $ok = $response->successful();
            $attempt = $log->attempt + 1;

            $log->update([
                'attempt' => $attempt,
                'status' => $ok ? 'sent' : 'failed',
                'status_code' => $response->status(),
                'response_body' => str($response->body())->limit(3000)->value(),
                'sent_at' => $ok ? now() : null,
                'next_retry_at' => $ok || $attempt >= 5 ? null : now()->addMinutes($attempt * 5),
            ]);
        } catch (\Throwable $exception) {
            $attempt = $log->attempt + 1;
            $log->update([
                'attempt' => $attempt,
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'next_retry_at' => $attempt >= 5 ? null : now()->addMinutes($attempt * 5),
            ]);
        }
    }
}
