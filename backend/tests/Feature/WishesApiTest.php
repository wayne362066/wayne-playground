<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Wish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WishesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_create_a_wish_that_is_immediately_public(): void
    {
        $response = $this->withCsrf()->postJson('/api/wishes', [
            'title' => '希望加入書單功能',
            'description' => '可以記錄想讀的書。',
            'category' => 'feature',
            'author_type' => 'guest',
            'author_name' => 'Wayne',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath('data.moderation_status', 'approved')
            ->assertJsonPath('data.author.name', 'Wayne')
            ->assertJsonMissingPath('data.events');

        $this->getJson('/api/wishes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '希望加入書單功能');
    }

    public function test_anonymous_wish_does_not_keep_a_display_name(): void
    {
        $this->withCsrf()->postJson('/api/wishes', [
            'title' => '希望加入匿名點子',
            'category' => 'other',
            'author_type' => 'anonymous',
            'author_name' => '不應保存',
        ])->assertCreated()
            ->assertJsonPath('data.author.name', null);

        $this->assertDatabaseHas('wishes', [
            'author_type' => 'anonymous',
            'author_name' => null,
        ]);
    }

    public function test_admin_can_update_wishes_and_records_status_history(): void
    {
        $this->actingAs($this->admin());
        $wish = $this->createWish();

        $this->withCsrf()->patchJson("/api/wishes/{$wish->id}", [
            'status' => 'planned',
            'title' => '更新後的願望',
        ])->assertOk()
            ->assertJsonPath('data.status', 'planned')
            ->assertJsonPath('data.title', '更新後的願望');

        $this->assertDatabaseHas('wish_events', [
            'wish_id' => $wish->id,
            'event_type' => 'status_changed',
            'from_value' => 'submitted',
            'to_value' => 'planned',
        ]);

        $this->assertDatabaseHas('wish_events', [
            'wish_id' => $wish->id,
            'event_type' => 'updated',
        ]);
    }

    public function test_hidden_and_deleted_wishes_only_appear_in_management_view_and_can_be_restored(): void
    {
        $this->actingAs($this->admin());
        $hidden = $this->createWish(['title' => '隱藏願望']);
        $deleted = $this->createWish(['title' => '刪除願望']);

        $this->withCsrf()->patchJson("/api/wishes/{$hidden->id}", [
            'moderation_status' => 'hidden',
        ])->assertOk();

        $this->withCsrf()->deleteJson("/api/wishes/{$deleted->id}")
            ->assertOk();

        $this->getJson('/api/wishes')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/wish-management')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->withCsrf()->postJson("/api/wish-management/{$deleted->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.is_deleted', false);

        $this->assertDatabaseHas('wish_events', [
            'wish_id' => $deleted->id,
            'event_type' => 'restored',
        ]);
    }

    public function test_guest_name_and_supported_values_are_validated(): void
    {
        $this->withCsrf()->postJson('/api/wishes', [
            'title' => '缺少名字',
            'category' => 'unknown',
            'author_type' => 'guest',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['category', 'author_name']);
    }

    public function test_switching_from_anonymous_to_guest_requires_a_name(): void
    {
        $this->actingAs($this->admin());
        $wish = $this->createWish([
            'author_type' => 'anonymous',
            'author_name' => null,
        ]);

        $this->withCsrf()->patchJson("/api/wishes/{$wish->id}", [
            'author_type' => 'guest',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['author_name']);
    }

    public function test_guest_cannot_use_management_or_mutation_endpoints(): void
    {
        $wish = $this->createWish();

        $this->getJson('/api/wish-management')
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', '沒有權限執行此操作');

        $this->withCsrf()->patchJson("/api/wishes/{$wish->id}", [
            'status' => 'planned',
        ])->assertForbidden();

        $this->withCsrf()->deleteJson("/api/wishes/{$wish->id}")
            ->assertForbidden();
    }

    public function test_moderator_permissions_do_not_grant_content_editing_or_history(): void
    {
        $wish = $this->createWish();
        $moderator = User::factory()->create();
        $role = Role::query()->create([
            'key' => 'wish-moderator',
            'name' => 'Wish moderator',
        ]);
        $role->permissions()->attach(
            Permission::query()
                ->whereIn('key', [
                    'wishes.manage.view',
                    'wishes.moderate',
                ])
                ->pluck('id')
        );
        $moderator->roles()->attach($role);
        $this->actingAs($moderator);

        $this->getJson('/api/wish-management')
            ->assertOk()
            ->assertJsonMissingPath('data.0.events');

        $this->withCsrf()->patchJson("/api/wishes/{$wish->id}", [
            'moderation_status' => 'hidden',
        ])->assertOk()
            ->assertJsonPath('data.moderation_status', 'hidden');

        $this->withCsrf()->patchJson("/api/wishes/{$wish->id}", [
            'title' => '不應允許的修改',
        ])->assertForbidden();
    }

    private function createWish(array $attributes = []): Wish
    {
        $wish = Wish::query()->create(array_merge([
            'title' => '測試願望',
            'description' => '測試內容',
            'category' => 'feature',
            'status' => 'submitted',
            'moderation_status' => 'approved',
            'visibility' => 'public',
            'author_type' => 'guest',
            'author_name' => '測試者',
        ], $attributes));

        $this->assertTrue(Str::isUlid($wish->id));

        return $wish;
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(
            Role::query()->where('key', 'admin')->firstOrFail()
        );

        return $user;
    }
}
