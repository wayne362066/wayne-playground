<?php

namespace App\Core\Services;

use App\Core\Access\AuthorizationService;
use App\Models\User;
use Illuminate\Support\Collection;

final class ModuleRegistry
{
    public function __construct(
        private readonly AuthorizationService $authorization,
    ) {}

    public function all(?User $user): Collection
    {
        return collect(config('modules', []))
            ->filter(fn (array $module): bool => (bool) ($module['enabled'] ?? false))
            ->filter(fn (array $module): bool => $this->authorization->allows(
                $user,
                "modules.{$module['key']}.view",
            ))
            ->sortBy('sort_order')
            ->values();
    }
}
