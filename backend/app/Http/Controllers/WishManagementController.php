<?php

namespace App\Http\Controllers;

use App\Http\Resources\WishResource;
use App\Http\Responses\ApiResponse;
use App\Models\Wish;
use App\Services\WishEventRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class WishManagementController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewManagement', Wish::class);

        $wishes = Wish::query()
            ->withTrashed()
            ->with('events')
            ->latest()
            ->get();

        return ApiResponse::success(WishResource::collection($wishes)->resolve());
    }

    public function restore(string $id, WishEventRecorder $recorder): JsonResponse
    {
        $wish = Wish::query()
            ->withTrashed()
            ->whereKey($id)
            ->firstOrFail();

        Gate::authorize('restore', $wish);

        if ($wish->trashed()) {
            $wish->restore();
            $recorder->restored($wish);
        }

        return ApiResponse::success(
            (new WishResource($wish->load('events')))->resolve(),
            '願望已恢復',
        );
    }
}
