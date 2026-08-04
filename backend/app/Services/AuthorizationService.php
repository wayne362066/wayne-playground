<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Collection;

class AuthorizationService
{
    /** @var array<string, Collection<int, string>> */
    private array $resolvedPermissions = [];

    /** @return Collection<int, string> */
    public function permissionsFor(?User $user): Collection
    {
        $cacheKey = $user?->id ?? 'guest';

        return $this->resolvedPermissions[$cacheKey] ??= Permission::query()
            ->whereHas('roles', function ($query) use ($user): void {
                if ($user) {
                    $query->whereHas(
                        'users',
                        fn ($users) => $users->whereKey($user->id),
                    );

                    return;
                }

                $query->where('key', 'guest');
            })
            ->orderBy('key')
            ->pluck('key')
            ->values();
    }

    public function allows(?User $user, string $permission): bool
    {
        return $this->permissionsFor($user)->containsStrict($permission);
    }

    public function forget(?User $user = null): void
    {
        if ($user) {
            unset($this->resolvedPermissions[$user->id]);

            return;
        }

        $this->resolvedPermissions = [];
    }
}
