<?php

namespace App\Modules\Access\Controllers;

use App\Core\Access\AccessManager;
use App\Core\Access\AuthorizationService;
use App\Core\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Access\Models\AuthorizationAudit;
use App\Modules\Access\Models\Permission;
use App\Modules\Access\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AccessManagementController extends Controller
{
    public function index(
        Request $request,
        AuthorizationService $authorization,
    ): JsonResponse {
        $roles = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => $this->roleData($role));
        $permissions = Permission::query()
            ->orderBy('module')
            ->orderBy('key')
            ->get()
            ->map(fn (Permission $permission): array => [
                'id' => $permission->id,
                'key' => $permission->key,
                'module' => $permission->module,
                'name' => $permission->name,
                'description' => $permission->description,
            ]);
        $users = User::query()
            ->with('roles')
            ->orderBy('username')
            ->get()
            ->map(fn (User $user): array => $this->userData($user));
        $audits = collect();

        if ($authorization->allows($request->user(), 'access.audit.view')) {
            $audits = AuthorizationAudit::query()
                ->with('actor')
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (AuthorizationAudit $audit): array => [
                    'id' => $audit->id,
                    'actor' => $audit->actor?->username ?? 'system',
                    'subject_type' => $audit->subject_type,
                    'subject_id' => $audit->subject_id,
                    'action' => $audit->action,
                    'before' => $audit->before,
                    'after' => $audit->after,
                    'ip_address' => $audit->ip_address,
                    'created_at' => $audit->created_at?->toISOString(),
                ]);
        }

        return ApiResponse::success([
            'roles' => $roles,
            'permissions' => $permissions,
            'users' => $users,
            'audits' => $audits,
        ]);
    }

    public function storeRole(
        Request $request,
        AccessManager $manager,
    ): JsonResponse {
        $data = $request->validate([
            'key' => [
                'required',
                'string',
                'max:64',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('roles', 'key'),
            ],
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permission_keys' => ['present', 'array'],
            'permission_keys.*' => [
                'string',
                'distinct',
                Rule::exists('permissions', 'key'),
            ],
        ]);

        $role = $manager->createRole(
            $request->user(),
            [
                'key' => Str::lower($data['key']),
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
            ],
            $data['permission_keys'],
            $request->ip(),
        );

        return ApiResponse::success(
            $this->roleData($role),
            '角色已建立',
            201,
        );
    }

    public function updateRole(
        Request $request,
        Role $role,
        AccessManager $manager,
    ): JsonResponse {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permission_keys' => ['present', 'array'],
            'permission_keys.*' => [
                'string',
                'distinct',
                Rule::exists('permissions', 'key'),
            ],
        ]);

        $role = $manager->updateRole(
            $request->user(),
            $role,
            [
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
            ],
            $data['permission_keys'],
            $request->ip(),
        );

        return ApiResponse::success(
            $this->roleData($role),
            '角色權限已更新',
        );
    }

    public function destroyRole(
        Request $request,
        Role $role,
        AccessManager $manager,
    ): JsonResponse {
        $manager->deleteRole($request->user(), $role, $request->ip());

        return ApiResponse::success(null, '角色已刪除');
    }

    public function updateUserRoles(
        Request $request,
        User $user,
        AccessManager $manager,
    ): JsonResponse {
        $data = $request->validate([
            'role_keys' => ['required', 'array', 'min:1'],
            'role_keys.*' => [
                'string',
                'distinct',
                Rule::exists('roles', 'key'),
            ],
        ]);

        $user = $manager->updateUserRoles(
            $request->user(),
            $user,
            $data['role_keys'],
            $request->ip(),
        );

        return ApiResponse::success(
            $this->userData($user),
            '使用者角色已更新',
        );
    }

    private function roleData(Role $role): array
    {
        return [
            'id' => $role->id,
            'key' => $role->key,
            'name' => $role->name,
            'description' => $role->description,
            'is_system' => $role->is_system,
            'user_count' => $role->users_count ?? $role->users()->count(),
            'permission_keys' => $role->permissions
                ->pluck('key')
                ->sort()
                ->values()
                ->all(),
        ];
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'role_keys' => $user->roles
                ->pluck('key')
                ->sort()
                ->values()
                ->all(),
        ];
    }
}
