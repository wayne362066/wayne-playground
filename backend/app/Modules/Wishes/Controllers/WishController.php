<?php

namespace App\Modules\Wishes\Controllers;

use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Modules\Wishes\Models\Wish;
use App\Modules\Wishes\Requests\StoreWishRequest;
use App\Modules\Wishes\Requests\UpdateWishRequest;
use App\Modules\Wishes\Resources\WishResource;
use App\Modules\Wishes\Services\WishEventRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WishController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Wish::class);

        $filters = $request->validate([
            'status' => ['nullable', 'in:'.implode(',', config('wishes.statuses'))],
            'category' => ['nullable', 'in:'.implode(',', config('wishes.categories'))],
        ]);

        $wishes = Wish::query()
            ->publiclyVisible()
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->latest()
            ->get();

        return ApiResponse::success(WishResource::collection($wishes)->resolve());
    }

    public function store(StoreWishRequest $request, WishEventRecorder $recorder): JsonResponse
    {
        Gate::authorize('create', Wish::class);

        $data = $request->validated();
        $data['public_id'] = (string) Str::ulid();
        $data['author_name'] = $data['author_type'] === 'anonymous'
            ? null
            : trim($data['author_name']);

        $wish = Wish::query()->create($data);
        $recorder->created($wish);

        return ApiResponse::success(
            (new WishResource($wish->load('events')))->resolve(),
            '願望已公開',
            201,
        );
    }

    public function show(Wish $wish): JsonResponse
    {
        Gate::authorize('view', $wish);
        abort_unless(
            $wish->moderation_status === 'approved' && $wish->visibility === 'public',
            404,
        );

        return ApiResponse::success((new WishResource($wish->load('events')))->resolve());
    }

    public function update(
        UpdateWishRequest $request,
        Wish $wish,
        WishEventRecorder $recorder,
    ): JsonResponse {
        Gate::authorize('update', $wish);

        $data = $request->validated();

        if (array_key_exists('status', $data) && $data['status'] !== $wish->status) {
            Gate::authorize('changeStatus', $wish);
        }

        if (
            (array_key_exists('visibility', $data) && $data['visibility'] !== $wish->visibility)
            || (array_key_exists('moderation_status', $data) && $data['moderation_status'] !== $wish->moderation_status)
        ) {
            Gate::authorize('hide', $wish);
        }

        $authorType = $data['author_type'] ?? $wish->author_type;
        if ($authorType === 'anonymous') {
            $data['author_name'] = null;
        } else {
            $authorName = trim((string) ($data['author_name'] ?? $wish->author_name));

            if ($authorName === '') {
                throw ValidationException::withMessages([
                    'author_name' => ['顯示暱稱時必須填寫暱稱。'],
                ]);
            }

            if (array_key_exists('author_name', $data)) {
                $data['author_name'] = $authorName;
            }
        }

        $original = $wish->getOriginal();
        $wish->fill($data);
        $wish->save();
        $recorder->changed($wish, $original, $wish->getChanges());

        return ApiResponse::success(
            (new WishResource($wish->load('events')))->resolve(),
            '願望已更新',
        );
    }

    public function destroy(Wish $wish, WishEventRecorder $recorder): JsonResponse
    {
        Gate::authorize('delete', $wish);

        $recorder->deleted($wish);
        $wish->delete();

        return ApiResponse::success(null, '願望已移至封存區');
    }
}
