<?php

namespace App\Services;

use App\Models\AuthorizationAudit;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccessManager
{
    public function __construct(
        private readonly AuthorizationService $authorization,
    ) {}

    public function createRole(
        User $actor,
        array $attributes,
        array $permissionKeys,
        ?string $ipAddress,
    ): Role {
        return DB::transaction(function () use (
            $actor,
            $attributes,
            $permissionKeys,
            $ipAddress,
        ): Role {
            $role = Role::query()->create([
                ...$attributes,
                'is_system' => false,
            ]);
            $this->syncPermissions($role, $permissionKeys);
            $this->audit(
                $actor,
                'role',
                $role->id,
                'role.created',
                [],
                $this->roleSnapshot($role),
                $ipAddress,
            );

            return $role->load('permissions');
        });
    }

    public function updateRole(
        User $actor,
        Role $role,
        array $attributes,
        array $permissionKeys,
        ?string $ipAddress,
    ): Role {
        return DB::transaction(function () use (
            $actor,
            $role,
            $attributes,
            $permissionKeys,
            $ipAddress,
        ): Role {
            $before = $this->roleSnapshot($role);
            $role->update($attributes);
            $this->syncPermissions($role, $permissionKeys);
            $this->ensureManagerRemains();
            $this->authorization->forget();
            $role->load('permissions');
            $this->audit(
                $actor,
                'role',
                $role->id,
                'role.updated',
                $before,
                $this->roleSnapshot($role),
                $ipAddress,
            );

            return $role;
        });
    }

    public function deleteRole(
        User $actor,
        Role $role,
        ?string $ipAddress,
    ): void {
        DB::transaction(function () use ($actor, $role, $ipAddress): void {
            if ($role->is_system) {
                throw ValidationException::withMessages([
                    'role' => ['系統角色不可刪除。'],
                ]);
            }

            if ($role->users()->exists()) {
                throw ValidationException::withMessages([
                    'role' => ['仍有使用者使用此角色，請先調整使用者角色。'],
                ]);
            }

            $before = $this->roleSnapshot($role);
            $role->delete();
            $this->ensureManagerRemains();
            $this->authorization->forget();
            $this->audit(
                $actor,
                'role',
                $role->id,
                'role.deleted',
                $before,
                [],
                $ipAddress,
            );
        });
    }

    public function updateUserRoles(
        User $actor,
        User $target,
        array $roleKeys,
        ?string $ipAddress,
    ): User {
        return DB::transaction(function () use (
            $actor,
            $target,
            $roleKeys,
            $ipAddress,
        ): User {
            if (in_array('guest', $roleKeys, true)) {
                throw ValidationException::withMessages([
                    'role_keys' => ['訪客角色只適用於未登入使用者。'],
                ]);
            }

            $before = $target->roles()->orderBy('key')->pluck('key')->all();
            $roleIds = Role::query()
                ->whereIn('key', $roleKeys)
                ->pluck('id')
                ->all();
            $target->roles()->sync($roleIds);
            $this->ensureManagerRemains();
            $this->authorization->forget();
            $after = $target->roles()->orderBy('key')->pluck('key')->all();
            $this->audit(
                $actor,
                'user',
                $target->id,
                'user.roles_updated',
                ['roles' => $before],
                ['roles' => $after],
                $ipAddress,
            );

            return $target->load('roles');
        });
    }

    private function syncPermissions(Role $role, array $permissionKeys): void
    {
        $permissionIds = Permission::query()
            ->whereIn('key', $permissionKeys)
            ->pluck('id')
            ->all();
        $role->permissions()->sync($permissionIds);
    }

    private function ensureManagerRemains(): void
    {
        $hasManager = User::query()
            ->whereHas(
                'roles.permissions',
                fn ($permissions) => $permissions->where('key', 'access.manage'),
            )
            ->exists();

        if (! $hasManager) {
            throw ValidationException::withMessages([
                'permissions' => ['系統至少必須保留一位具備權限管理能力的使用者。'],
            ]);
        }
    }

    private function roleSnapshot(Role $role): array
    {
        return [
            'key' => $role->key,
            'name' => $role->name,
            'description' => $role->description,
            'permissions' => $role->permissions()
                ->orderBy('key')
                ->pluck('key')
                ->all(),
        ];
    }

    private function audit(
        ?User $actor,
        string $subjectType,
        ?string $subjectId,
        string $action,
        array $before,
        array $after,
        ?string $ipAddress,
    ): void {
        AuthorizationAudit::query()->create([
            'actor_id' => $actor?->id,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'ip_address' => $ipAddress,
        ]);
    }
}
