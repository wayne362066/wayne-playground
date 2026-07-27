<?php

namespace App\Core\Access;

use Illuminate\Support\Collection;
use RuntimeException;

class PermissionCatalog
{
    /** @return Collection<int, array<string, mixed>> */
    public function all(): Collection
    {
        $permissions = collect(glob(app_path('Modules/*/permissions.php')) ?: [])
            ->sort()
            ->flatMap(function (string $path): array {
                $definitions = require $path;

                if (! is_array($definitions)) {
                    throw new RuntimeException(
                        "權限定義檔必須回傳 array：{$path}"
                    );
                }

                return $definitions;
            })
            ->map(function (mixed $permission): array {
                if (
                    ! is_array($permission)
                    || ! isset(
                        $permission['key'],
                        $permission['module'],
                        $permission['name'],
                    )
                ) {
                    throw new RuntimeException(
                        '每項權限定義都必須包含 key、module 與 name。'
                    );
                }

                return [
                    'key' => (string) $permission['key'],
                    'module' => (string) $permission['module'],
                    'name' => (string) $permission['name'],
                    'description' => $permission['description'] ?? null,
                    'default_roles' => array_values(array_unique(
                        $permission['default_roles'] ?? [],
                    )),
                ];
            })
            ->values();

        $duplicates = $permissions
            ->groupBy('key')
            ->filter(fn (Collection $items): bool => $items->count() > 1)
            ->keys();

        if ($duplicates->isNotEmpty()) {
            throw new RuntimeException(
                '權限 key 重複：'.$duplicates->implode(', ')
            );
        }

        return $permissions->sortBy('key')->values();
    }
}
