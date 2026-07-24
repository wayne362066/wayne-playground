<?php

namespace App\Core\Services;

use Illuminate\Support\Collection;

final class ModuleRegistry
{
    public function all(): Collection
    {
        return collect(config('modules', []))
            ->filter(fn (array $module): bool => (bool) ($module['enabled'] ?? false))
            ->sortBy('sort_order')
            ->values();
    }
}
