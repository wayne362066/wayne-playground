<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccessControlApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_a_user_with_access_management_permission_can_open_the_console(): void
    {
        $this->getJson('/api/admin/access')
            ->assertForbidden()
            ->assertJsonPath('message', '沒有權限執行此操作');

        $this->actingAs($this->admin())
            ->getJson('/api/admin/access')
            ->assertOk()
            ->assertJsonPath('data.roles.0.is_system', true)
            ->assertJsonFragment(['key' => 'access.manage']);
    }

    public function test_admin_can_create_a_role_assign_it_and_review_the_audit_log(): void
    {
        $admin = $this->admin(['nickname' => '站長 Wayne']);
        $member = User::factory()->create();
        $member->roles()->attach($this->role('member'));
        $this->actingAs($admin);

        $response = $this->withCsrf()->postJson('/api/admin/access/roles', [
            'key' => 'wish-reviewer',
            'name' => '願望審核員',
            'description' => '只能檢視與審核願望。',
            'permission_keys' => [
                'modules.wishes.view',
                'wishes.view',
                'wishes.manage.view',
                'wishes.moderate',
            ],
        ])->assertCreated()
            ->assertJsonPath('data.key', 'wish-reviewer')
            ->assertJsonPath('data.is_system', false);

        $roleId = $response->json('data.id');
        $this->assertTrue(Str::isUlid($roleId));

        $this->withCsrf()->patchJson("/api/admin/access/users/{$member->id}/roles", [
            'role_keys' => ['wish-reviewer'],
        ])->assertOk()
            ->assertJsonPath('data.role_keys.0', 'wish-reviewer');

        $this->assertSame(
            ['wish-reviewer'],
            $member->fresh()->roles()->pluck('key')->all(),
        );

        $this->getJson('/api/admin/access')
            ->assertOk()
            ->assertJsonFragment(['action' => 'role.created'])
            ->assertJsonFragment(['action' => 'user.roles_updated'])
            ->assertJsonFragment(['actor' => '站長 Wayne']);
    }

    public function test_the_last_access_manager_cannot_remove_their_own_management_path(): void
    {
        $admin = $this->admin();
        $adminRole = $this->role('admin');
        $permissionKeys = Permission::query()
            ->where('key', '!=', 'access.manage')
            ->pluck('key')
            ->all();

        $this->actingAs($admin)
            ->withCsrf()
            ->patchJson("/api/admin/access/roles/{$adminRole->id}", [
                'name' => $adminRole->name,
                'description' => $adminRole->description,
                'permission_keys' => $permissionKeys,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['permissions']);

        $this->assertTrue(
            $adminRole->permissions()
                ->where('key', 'access.manage')
                ->exists()
        );
    }

    public function test_system_roles_cannot_be_deleted_and_guest_cannot_be_assigned_to_an_account(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        $member->roles()->attach($this->role('member'));
        $this->actingAs($admin);

        $this->withCsrf()
            ->deleteJson("/api/admin/access/roles/{$this->role('member')->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);

        $this->withCsrf()->patchJson("/api/admin/access/users/{$member->id}/roles", [
            'role_keys' => ['guest'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['role_keys']);
    }

    public function test_a_logged_in_user_without_lottery_permission_is_forbidden(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'key' => 'viewer',
            'name' => 'Viewer',
        ]);
        $user->roles()->attach($role);

        $this->actingAs($user)
            ->withCsrf()
            ->postJson('/api/lottery/power/simulate', [
                'ticket_count' => 1,
                'mode' => 'single',
            ])
            ->assertForbidden();
    }

    private function admin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach($this->role('admin'));

        return $user;
    }

    private function role(string $key): Role
    {
        return Role::query()->where('key', $key)->firstOrFail();
    }
}
