<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Partner;
use App\Models\User;
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
        $authorization = $request->header('Authorization', '');
        if (! str_starts_with($authorization, 'Bearer ')) {
            return $this->unauthorized('Missing Bearer token.');
        }

        $bearer = trim(substr($authorization, 7));
        if ($bearer === '') {
            return $this->unauthorized('Missing Bearer token.');
        }

        $token = PersonalAccessToken::findToken($bearer);
        $partner = null;
        if ($token && $token->tokenable instanceof User) {
            $partner = Partner::query()->find($token->tokenable->getKey());
            if (! $partner) {
                return $this->unauthorized('Partner account not found.');
            }
            if (! $partner->hasRole('partner')) {
                return $this->unauthorized('User does not have partner role.');
            }
            if (! $partner->is_active || $partner->status !== 'active') {
                return $this->unauthorized('Partner account is inactive.');
            }
        }

        if (! $partner) {
            return $this->unauthorized('Invalid credentials.');
        }

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
