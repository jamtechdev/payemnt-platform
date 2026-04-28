<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\Occupation;
use App\Models\Relationship;
use App\Models\TaskType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class LookupController extends BaseApiController
{
    // ─── Occupations ────────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/api/v1/occupations',
        operationId: 'occupationStore',
        summary: 'Create or update an occupation (partner auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['Occupations'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name',   type: 'string', example: 'Engineer'),
                    new OA\Property(property: 'status', type: 'string', example: 'Active'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Occupation created or updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function occupationStore(Request $request): JsonResponse
    {
        $partner   = $request->attributes->get('partner');
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:100'],
        ]);

        $occupation = Occupation::updateOrCreate(
            ['partner_id' => $partner->id, 'name' => $validated['name']],
            ['status' => $validated['status'] ?? 'Active']
        );

        return $this->success($occupation, 200);
    }

    #[OA\Delete(
        path: '/api/v1/occupations',
        operationId: 'occupationDestroy',
        summary: 'Delete all occupations of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Occupations'],
        responses: [
            new OA\Response(response: 200, description: 'Occupations deleted'),
            new OA\Response(response: 404, description: 'No occupations found'),
        ]
    )]
    public function occupationDestroy(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $deleted = Occupation::where('partner_id', $partner->id)->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No occupations found for this partner.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }

    // ─── Relationships ───────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/api/v1/relationships',
        operationId: 'relationshipStore',
        summary: 'Create or update a relationship (partner auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['Relationships'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name',   type: 'string', example: 'Spouse'),
                    new OA\Property(property: 'status', type: 'string', example: 'Active'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Relationship created or updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function relationshipStore(Request $request): JsonResponse
    {
        $partner   = $request->attributes->get('partner');
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:100'],
        ]);

        $relationship = Relationship::updateOrCreate(
            ['partner_id' => $partner->id, 'name' => $validated['name']],
            ['status' => $validated['status'] ?? 'Active']
        );

        return $this->success($relationship, 200);
    }

    #[OA\Delete(
        path: '/api/v1/relationships',
        operationId: 'relationshipDestroy',
        summary: 'Delete all relationships of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Relationships'],
        responses: [
            new OA\Response(response: 200, description: 'Relationships deleted'),
            new OA\Response(response: 404, description: 'No relationships found'),
        ]
    )]
    public function relationshipDestroy(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $deleted = Relationship::where('partner_id', $partner->id)->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No relationships found for this partner.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }

    // ─── Task Types ──────────────────────────────────────────────────────────────

    #[OA\Post(
        path: '/api/v1/task-types',
        operationId: 'taskTypeStore',
        summary: 'Create or update a task type (partner auto-set from API key)',
        security: [['sanctum' => []]],
        tags: ['Task Types'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name',   type: 'string', example: 'Follow Up'),
                    new OA\Property(property: 'status', type: 'string', example: 'Active'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Task type created or updated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function taskTypeStore(Request $request): JsonResponse
    {
        $partner   = $request->attributes->get('partner');
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:100'],
        ]);

        $taskType = TaskType::updateOrCreate(
            ['partner_id' => $partner->id, 'name' => $validated['name']],
            ['status' => $validated['status'] ?? 'Active']
        );

        return $this->success($taskType, 200);
    }

    #[OA\Delete(
        path: '/api/v1/task-types',
        operationId: 'taskTypeDestroy',
        summary: 'Delete all task types of authenticated partner',
        security: [['sanctum' => []]],
        tags: ['Task Types'],
        responses: [
            new OA\Response(response: 200, description: 'Task types deleted'),
            new OA\Response(response: 404, description: 'No task types found'),
        ]
    )]
    public function taskTypeDestroy(Request $request): JsonResponse
    {
        $partner = $request->attributes->get('partner');
        $deleted = TaskType::where('partner_id', $partner->id)->delete();

        if ($deleted === 0) {
            return $this->error('NOT_FOUND', 'No task types found for this partner.', [], 404);
        }

        return $this->success(['deleted_count' => $deleted]);
    }
}
