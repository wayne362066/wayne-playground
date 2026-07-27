<?php

namespace App\Modules\Home\Controllers;

use App\Core\Http\ApiResponse;
use App\Core\Services\ModuleRegistry;
use App\Http\Controllers\Controller;
use App\Modules\Home\Resources\ModuleResource;
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
