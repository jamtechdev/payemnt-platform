<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class VerifyController extends BaseApiController
{
    #[OA\Post(
        path: '/api/v1/verify',
        operationId: 'partnerVerify',
        summary: 'Connect and verify an external partner platform',
        tags: ['Connect'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['partner_code', 'api_key', 'base_url'],
                properties: [
                    new OA\Property(property: 'partner_code', type: 'string', example: 'SWAP_CIRCLE'),
                    new OA\Property(property: 'api_key',      type: 'string', example: 'your-api-key-here'),
                    new OA\Property(property: 'base_url',     type: 'string', format: 'uri', example: 'https://app.swapcircle.com'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Partner verified and connected successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success',      type: 'boolean', example: true),
                        new OA\Property(property: 'message',      type: 'string',  example: 'Partner connected successfully.'),
                        new OA\Property(property: 'partner_name', type: 'string',  example: 'SwapCircle'),
                        new OA\Property(property: 'connected_at', type: 'string',  format: 'date-time', example: '2024-01-01T10:00:00Z'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Partner not found'),
            new OA\Response(response: 401, description: 'Invalid API key or partner inactive'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
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
