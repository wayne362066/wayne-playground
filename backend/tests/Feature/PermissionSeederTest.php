<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionCatalog;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_discovers_missing_module_permissions_without_overwriting_manual_role_choices(): void
    {
        $member = Role::query()->where('key', 'member')->firstOrFail();
        $guest = Role::query()->where('key', 'guest')->firstOrFail();
        $admin = Role::query()->where('key', 'admin')->firstOrFail();
        $manuallyRemoved = Permission::query()
            ->where('key', 'wishes.create')
            ->firstOrFail();
        $member->permissions()->detach($manuallyRemoved);
        Permission::query()
            ->where('key', 'lottery.simulate')
            ->delete();

        $this->seed(PermissionSeeder::class);
        $this->seed(PermissionSeeder::class);

        $restored = Permission::query()
            ->where('key', 'lottery.simulate')
            ->sole();

        $this->assertTrue($guest->permissions()->whereKey($restored->id)->exists());
        $this->assertTrue($member->permissions()->whereKey($restored->id)->exists());
        $this->assertTrue($admin->permissions()->whereKey($restored->id)->exists());
        $this->assertFalse(
            $member->permissions()->whereKey($manuallyRemoved->id)->exists()
        );
        $this->assertTrue(
            $admin->permissions()->whereKey($manuallyRemoved->id)->exists()
        );
        $this->assertSame(
            app(PermissionCatalog::class)->all()->count(),
            Permission::query()->count(),
        );
        $this->assertDatabaseHas('authorization_audits', [
            'subject_type' => 'permission',
            'subject_id' => $restored->id,
            'action' => 'permission.seeded',
        ]);
    }
}
