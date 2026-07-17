<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Every non-2xx response uses the same envelope:
 * { "message": "...", "errors": {...}, "code": "MACHINE_READABLE" }
 */
class ApiResponse
{
    public static function error(string $message, string $code, int $status, array $errors = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => (object) $errors,
            'code' => $code,
        ], $status);
    }
}
