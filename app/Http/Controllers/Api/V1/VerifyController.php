<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class VerifyController extends BaseApiController
{
    // Verify — hidden from Swagger
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'partner_code' => ['required', 'string'],
            'api_key'      => ['required', 'string'],
            'base_url'     => ['required', 'url'],
        ]);

        $partner = Partner::where('partner_code', $validated['partner_code'])->first();

        if (! $partner) {
            return $this->error('NOT_FOUND', 'Partner not found.', [], 404);
        }

        if ($partner->status !== 'active') {
            return $this->error('PARTNER_INACTIVE', 'Partner account is inactive.', [], 401);
        }

        if (hash('sha256', $validated['api_key']) !== $partner->api_key) {
            return $this->error('INVALID_API_KEY', 'Invalid API key.', [], 401);
        }

        $partner->forceFill([
            'connected_base_url' => $validated['base_url'],
            'connected_at'       => now(),
            'last_seen_at'       => now(),
        ])->save();

        return $this->success([
            'partner_name' => $partner->name,
            'connected_at' => $partner->connected_at,
        ]);
    }
}
