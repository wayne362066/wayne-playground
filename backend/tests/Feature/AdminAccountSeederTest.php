<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Access\Models\Permission;
use Database\Seeders\AdminAccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAccountSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_idempotently_creates_the_development_admin_with_every_permission(): void
    {
        $this->seed(AdminAccountSeeder::class);
        $this->seed(AdminAccountSeeder::class);

        $user = User::query()->where('username', 'admin')->sole();

        $this->assertTrue(Str::isUlid($user->id));
        $this->assertTrue(Hash::check('admin12345', $user->password));
        $this->assertSame(['admin'], $user->roles()->pluck('key')->all());
        $this->assertSame(
            Permission::query()->count(),
            $user->roles()
                ->where('roles.key', 'admin')
                ->firstOrFail()
                ->permissions()
                ->count(),
        );
        $this->assertSame(
            1,
            User::query()->where('username', 'admin')->count(),
        );
    }
}
