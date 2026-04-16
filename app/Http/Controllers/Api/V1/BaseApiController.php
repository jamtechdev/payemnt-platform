<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseApiController extends Controller
{
    protected function success(mixed $data = [], int $status = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => (object) $meta,
        ], $status);
    }

    protected function error(string $errorCode, string $message, array $details = [], int $status = 400): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'error_code' => $errorCode,
            'message' => $message,
            'details' => $details,
        ], $status);
    }

    protected function paginated(\Illuminate\Contracts\Pagination\LengthAwarePaginator $paginator, int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $paginator->items(),
            'meta' => [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                ],
            ],
        ], $status);
    }
}
