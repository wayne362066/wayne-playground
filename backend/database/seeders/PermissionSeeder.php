<?php

namespace Database\Seeders;

use App\Core\Access\PermissionCatalog;
use App\Modules\Access\Models\AuthorizationAudit;
use App\Modules\Access\Models\Permission;
use App\Modules\Access\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(PermissionCatalog $catalog): void
    {
        foreach ($catalog->all() as $definition) {
            $permission = Permission::query()
                ->where('key', $definition['key'])
                ->first();
            $isNew = $permission === null;
            $permission = Permission::query()->updateOrCreate(
                ['key' => $definition['key']],
                [
                    'module' => $definition['module'],
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                ],
            );
            $roleKeys = $isNew
                ? [...$definition['default_roles'], 'admin']
                : ['admin'];
            $roleIds = Role::query()
                ->whereIn('key', array_unique($roleKeys))
                ->pluck('id')
                ->all();

            $permission->roles()->syncWithoutDetaching($roleIds);

            if ($isNew) {
                AuthorizationAudit::query()->create([
                    'actor_id' => null,
                    'subject_type' => 'permission',
                    'subject_id' => $permission->id,
                    'action' => 'permission.seeded',
                    'before' => [],
                    'after' => [
                        'key' => $permission->key,
                        'default_roles' => array_values(array_unique($roleKeys)),
                    ],
                    'ip_address' => null,
                ]);
            }
        }

        $this->command?->info('模組權限目錄已同步。');
    }
}
