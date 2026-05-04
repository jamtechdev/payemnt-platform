<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Partner;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePartnerApi
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = (string) $request->bearerToken();
        if ($bearer === '') {
            return $this->unauthorized('Missing Bearer token.');
        }

        $token = PersonalAccessToken::findToken($bearer);
        if (! $token || $token->tokenable_type !== Partner::class) {
            return $this->unauthorized('Invalid credentials.');
        }

        $partner = Partner::query()->find($token->tokenable_id);
        if (! $partner) {
            return $this->unauthorized('Partner account not found.');
        }
        if ($partner->status !== 'active') {
            return $this->unauthorized('Partner account is inactive.');
        }

        $token->forceFill(['last_used_at' => now()])->save();

        $partner->forceFill([
            'last_seen_at' => now(),
            'connected_at' => $partner->connected_at ?? now(),
        ])->save();

        $request->attributes->set('partner', $partner);

        return $next($request);
    }

    private function unauthorized(string $message): JsonResponse
    {
        return new JsonResponse([
            'status' => 'error',
            'error_code' => 'INVALID_CREDENTIALS',
            'message' => $message,
        ], 401);
    }
}
