<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('modules') as $module) {
            Module::query()->updateOrCreate(
                ['key' => $module['key']],
                $module,
            );
        }
    }
}
