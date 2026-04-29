<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\RateApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class RateApiController extends BaseApiController
{
    #[OA\Post(
        path: '/api/v1/rate-apis',
        operationId: 'rateApiSwap',
        summary: 'Bulk create or update rate APIs (partner auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['Rate APIs'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['data'],
                properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(
                            required: ['name', 'url', 'status'],
                            properties: [
                                new OA\Property(property: 'name',   type: 'string', example: 'Exchangerate'),
                                new OA\Property(property: 'url',    type: 'string', example: 'https://api.exchangerate.host/latest'),
                                new OA\Property(property: 'status', type: 'string', example: 'Active'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Rate APIs swapped successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $validated = $request->validate([
            'data'          => ['required', 'array', 'min:1'],
            'data.*.name'   => ['required', 'string'],
            'data.*.url'    => ['required', 'string'],
            'data.*.status' => ['required', 'string'],
        ]);

        $upserted = [];
        foreach ($validated['data'] as $item) {
            $upserted[] = RateApi::updateOrCreate(
                [
                    'name'       => $item['name'],
                    'partner_id' => $partner->id,
                ],
                array_merge($item, [
                    'partner_id'    => $partner->id,
                    'partner_code'  => $partner->partner_code,
                    'from_platform' => 1,
                ])
            );
        }

        return $this->success($upserted, 200);
    }

    #[OA\Delete(
        path: '/api/v1/rate-apis',
        operationId: 'rateApiUnswap',
        summary: 'Permanently delete all rate APIs of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Rate APIs'],
        responses: [
            new OA\Response(response: 200, description: 'Rate APIs deleted'),
            new OA\Response(response: 404, description: 'No rate APIs found'),
        ]
    )]
    public function destroy(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $deleted = RateApi::where('partner_id', $partner->id)->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No rate APIs found for this partner.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }
}
