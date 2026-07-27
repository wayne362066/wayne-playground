<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Access\Models\Role;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('AdminAccountSeeder 不可在 production 執行。');
        }

        $user = User::query()->updateOrCreate(
            ['username' => 'admin'],
            ['password' => 'admin12345'],
        );
        $adminRole = Role::query()
            ->where('key', 'admin')
            ->firstOrFail();

        $user->roles()->syncWithoutDetaching([$adminRole->id]);

        $this->command?->info('開發管理員 admin 已建立並取得完整權限。');
    }
}
