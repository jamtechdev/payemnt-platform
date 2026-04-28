<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\ConnectCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ConnectCategoryController extends BaseApiController
{
    #[OA\Post(
        path: '/api/v1/partner/connect-categories',
        operationId: 'connectCategoryStore',
        summary: 'Create a connect category',
        security: [['sanctum' => []]],
        tags: ['Connect Categories'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['category_code', 'name', 'status'],
                properties: [
                    new OA\Property(property: 'category_code', type: 'string', example: 'CAT_001'),
                    new OA\Property(property: 'name',          type: 'string', example: 'Travel'),
                    new OA\Property(property: 'icon_url',      type: 'string', format: 'uri', example: 'https://example.com/icon.png'),
                    new OA\Property(property: 'status',        type: 'string', example: 'active'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Category created or updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $validated = $request->validate([
            'category_code' => ['required', 'string', 'max:100'],
            'name'          => ['required', 'string', 'max:255'],
            'icon_url'      => ['nullable', 'url', 'max:500'],
            'status'        => ['required', 'string', 'in:active,inactive'],
        ]);

        $category = ConnectCategory::updateOrCreate(
            [
                'category_code' => $validated['category_code'],
                'partner_id'    => $partner->id,
            ],
            array_merge($validated, [
                'partner_id'    => $partner->id,
                'partner_code'  => $partner->partner_code,
                'from_platform' => 1,
            ])
        );

        return $this->success($category, 200);
    }

    #[OA\Delete(
        path: '/api/v1/partner/connect-categories',
        operationId: 'connectCategoryDestroy',
        summary: 'Permanently delete all connect categories of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Connect Categories'],
        responses: [
            new OA\Response(response: 200, description: 'Categories deleted'),
            new OA\Response(response: 404, description: 'No categories found'),
        ]
    )]
    public function destroy(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $deleted = ConnectCategory::where('partner_id', $partner->id)->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No connect categories found for this partner.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }
}
