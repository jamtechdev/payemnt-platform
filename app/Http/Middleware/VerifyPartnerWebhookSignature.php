<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPartnerWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $partner = $request->attributes->get('partner');
        if (! $partner || ! $partner->webhook_secret) {
            return $next($request);
        }

        $headerSignature = (string) $request->header('X-Webhook-Signature', '');
        if ($headerSignature === '') {
            return response()->json([
                'status' => 'error',
                'error_code' => 'SIGNATURE_MISSING',
                'message' => 'Webhook signature header is required.',
            ], 401);
        }

        $computed = hash_hmac('sha256', (string) $request->getContent(), $partner->webhook_secret);
        if (! hash_equals($computed, $headerSignature)) {
            return response()->json([
                'status' => 'error',
                'error_code' => 'SIGNATURE_INVALID',
                'message' => 'Invalid webhook signature.',
            ], 401);
        }

        return $next($request);
    }
}
