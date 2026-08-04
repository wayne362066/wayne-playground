<?php

namespace App\Http\Controllers;

use App\Http\Resources\ModuleResource;
use App\Http\Responses\ApiResponse;
use App\Services\ModuleRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ModuleController extends Controller
{
    public function __invoke(
        Request $request,
        ModuleRegistry $registry,
    ): JsonResponse {
        $modules = ModuleResource::collection(
            $registry->all($request->user())
        )->resolve();

        return ApiResponse::success($modules);
    }
}
