<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditApiUsage
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->is('api/*')) {
            $actor = $request->user();
            $partner = $request->attributes->get('partner');

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
                ],
                'occurred_at' => now(),
            ]);
        }

        return $response;
    }
}
