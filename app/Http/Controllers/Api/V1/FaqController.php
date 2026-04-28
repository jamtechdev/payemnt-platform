<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class FaqController extends BaseApiController
{
    #[OA\Post(
        path: '/api/v1/partner/faqs/swap',
        operationId: 'faqSwap',
        summary: 'Bulk create or update FAQs (partner auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['FAQs'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['faqs'],
                properties: [
                    new OA\Property(
                        property: 'faqs',
                        type: 'array',
                        items: new OA\Items(
                            required: ['faq_code', 'question', 'answer', 'status'],
                            properties: [
                                new OA\Property(property: 'faq_code',  type: 'string', example: 'FAQ_001'),
                                new OA\Property(property: 'question',  type: 'string', example: 'What is SwapCircle?'),
                                new OA\Property(property: 'answer',    type: 'string', example: 'SwapCircle is a platform...'),
                                new OA\Property(property: 'status',    type: 'string', example: 'active'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'FAQs swapped successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function swap(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $validated = $request->validate([
            'faqs'              => ['required', 'array', 'min:1'],
            'faqs.*.faq_code'   => ['required', 'string', 'max:100'],
            'faqs.*.question'   => ['required', 'string'],
            'faqs.*.answer'     => ['required', 'string'],
            'faqs.*.status'     => ['required', 'string', 'in:active,inactive'],
        ]);

        $upserted = [];
        foreach ($validated['faqs'] as $item) {
            $upserted[] = Faq::updateOrCreate(
                [
                    'faq_code'   => $item['faq_code'],
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
        path: '/api/v1/partner/faqs/unswap',
        operationId: 'faqUnswap',
        summary: 'Permanently delete all FAQs of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['FAQs'],
        responses: [
            new OA\Response(response: 200, description: 'FAQs deleted'),
            new OA\Response(response: 404, description: 'No FAQs found'),
        ]
    )]
    public function unswap(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');

        $deleted = Faq::where('partner_id', $partner->id)->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No FAQs found for this partner.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }
}
