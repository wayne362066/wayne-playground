<?php

namespace App\Modules\Auth\Controllers;

use App\Core\Access\AuthorizationService;
use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Access\Models\Role;
use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Requests\RegisterRequest;
use App\Modules\Auth\Resources\AccountResource;
use App\Modules\Auth\Services\PasswordAuthenticator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create($request->safe()->only([
            'username',
            'password',
        ]));
        $user->roles()->attach(
            Role::query()->where('key', 'member')->valueOrFail('id')
        );

        Auth::login($user);
        $request->session()->regenerate();

        return ApiResponse::success(
            (new AccountResource($user))->resolve(),
            '帳戶建立成功',
            201,
        );
    }

    public function login(
        LoginRequest $request,
        PasswordAuthenticator $authenticator,
    ): JsonResponse {
        $credentials = $request->validated();
        $user = $authenticator->attempt(
            $credentials['username'],
            $credentials['password'],
        );

        if (! $user) {
            return ApiResponse::error('帳號或密碼錯誤', 401);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return ApiResponse::success(
            (new AccountResource($user))->resolve(),
            '登入成功',
        );
    }

    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        return ApiResponse::success(
            $user ? (new AccountResource($user))->resolve() : null,
        );
    }

    public function permissions(
        Request $request,
        AuthorizationService $authorization,
    ): JsonResponse {
        return ApiResponse::success(
            $authorization->permissionsFor($request->user())->all()
        );
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return ApiResponse::success(null, '已登出');
    }
}
