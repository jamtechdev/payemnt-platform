<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Support\PartnerIntegrationGuide;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PartnerApiGuideController extends BaseApiController
{
    #[OA\Get(
        path: '/api/v1/partner/guide',
        operationId: 'partnerApiGuide',
        summary: 'Public JSON integration guide',
        description: 'No authentication. Returns `data` with steps, endpoint paths, `public_base_url` (from APP_URL), and Swap Circle reference notes. For a full HTML doc use GET /docs/partner-api.',
        tags: ['Guide'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success envelope',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'success'),
                        new OA\Property(property: 'data', type: 'object', description: 'Guide payload'),
                    ]
                )
            ),
        ]
    )]
    public function __invoke(): JsonResponse
    {
        return $this->success(PartnerIntegrationGuide::payload());
    }
}
