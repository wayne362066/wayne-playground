<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = '操作成功',
        int $status = 200,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    public static function validationError(array $errors): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => '資料驗證失敗',
            'errors' => $errors,
        ], 422);
    }

    public static function error(
        string $message = '系統發生錯誤',
        int $status = 500,
        array $context = [],
    ): JsonResponse {
        return response()->json(array_filter([
            'success' => false,
            'message' => $message,
            'errors' => $context ?: null,
        ], static fn (mixed $value): bool => $value !== null), $status);
    }
}
