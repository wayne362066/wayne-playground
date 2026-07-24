<?php

namespace App\Modules\Home\Controllers;

use App\Core\Http\ApiResponse;
use App\Core\Services\ModuleRegistry;
use App\Http\Controllers\Controller;
use App\Modules\Home\Resources\ModuleResource;
use Illuminate\Http\JsonResponse;

final class ModuleController extends Controller
{
    public function __invoke(ModuleRegistry $registry): JsonResponse
    {
        $modules = ModuleResource::collection($registry->all())->resolve();

        return ApiResponse::success($modules);
    }
}
