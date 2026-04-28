<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\ConnectArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ConnectArticleController extends BaseApiController
{
    #[OA\Post(
        path: '/api/v1/partner/connect-articles/swap',
        operationId: 'connectArticleSwap',
        summary: 'Bulk create or update connect articles (partner auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['Connect Articles'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['articles'],
                properties: [
                    new OA\Property(
                        property: 'articles',
                        type: 'array',
                        items: new OA\Items(
                            required: ['article_code', 'category_code', 'title', 'status'],
                            properties: [
                                new OA\Property(property: 'article_code',  type: 'string', example: 'ART_001'),
                                new OA\Property(property: 'category_code', type: 'string', example: 'CAT_001'),
                                new OA\Property(property: 'title',         type: 'string', example: 'Travel Tips'),
                                new OA\Property(property: 'description',   type: 'string', example: 'Article description'),
                                new OA\Property(property: 'image_url',     type: 'string', format: 'uri', example: 'https://example.com/image.jpg'),
                                new OA\Property(property: 'status',        type: 'string', example: 'active'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Articles swapped successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function swap(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $validated = $request->validate([
            'articles'                => ['required', 'array', 'min:1'],
            'articles.*.article_code' => ['required', 'string', 'max:100'],
            'articles.*.category_code'=> ['required', 'string', 'max:100'],
            'articles.*.title'        => ['required', 'string', 'max:255'],
            'articles.*.description'  => ['nullable', 'string'],
            'articles.*.image_url'    => ['nullable', 'url', 'max:500'],
            'articles.*.status'       => ['required', 'string', 'in:active,inactive'],
        ]);

        $upserted = [];
        foreach ($validated['articles'] as $item) {
            $upserted[] = ConnectArticle::updateOrCreate(
                [
                    'article_code' => $item['article_code'],
                    'partner_id'   => $partner->id,
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
        path: '/api/v1/partner/connect-articles/unswap',
        operationId: 'connectArticleUnswap',
        summary: 'Permanently delete all connect articles of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Connect Articles'],
        responses: [
            new OA\Response(response: 200, description: 'Articles deleted'),
            new OA\Response(response: 404, description: 'No articles found'),
        ]
    )]
    public function unswap(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $deleted = ConnectArticle::where('partner_id', $partner->id)->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No connect articles found for this partner.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }
}
