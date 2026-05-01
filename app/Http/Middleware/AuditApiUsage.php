<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\ApiLog;
use App\Models\AuditLog;
use App\Support\ApiPayloadSanitizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditApiUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);
        $response = $next($request);
        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

        if ($request->is('api/*')) {
            $actor = $request->user();
            $partner = $request->attributes->get('partner');
            $responseBody = json_decode((string) $response->getContent(), true);
            $requestBody = ApiPayloadSanitizer::sanitize($request->all());

            AuditLog::query()->create([
                'actor_user_id' => $actor?->id,
                'partner_id' => $partner?->id,
                'action' => 'api_usage',
                'entity_type' => 'route',
                'entity_id' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'changes' => [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'status' => $response->getStatusCode(),
                    'duration_ms' => $durationMs,
                    'latency_bucket' => $durationMs < 200 ? 'fast' : ($durationMs < 1000 ? 'normal' : 'slow'),
                    'outcome' => $response->getStatusCode() >= 400 ? 'failure' : 'success',
                ],
                'occurred_at' => now(),
            ]);

            ApiLog::query()->create([
                'partner_id' => $partner?->id,
                'method' => $request->method(),
                'path' => $request->path(),
                'endpoint_group' => str($request->path())->before('/')->value(),
                'request_body' => $requestBody,
                'response_body' => is_array($responseBody) ? $responseBody : null,
                'status_code' => $response->getStatusCode(),
                'response_time_ms' => $durationMs,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'source' => 'partner_api',
                'correlation_id' => (string) ($request->headers->get('X-Correlation-Id') ?? $request->headers->get('X-Request-Id')),
                'requested_at' => now(),
            ]);
        }

        return $response;
    }
}
