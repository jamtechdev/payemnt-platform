<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseApiController extends Controller
{
    protected function success(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $data,
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
}
