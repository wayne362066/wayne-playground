<?php

namespace Tests\Feature;

use App\Modules\Wishes\Models\Wish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_create_a_wish_that_is_immediately_public(): void
    {
        $response = $this->postJson('/api/wishes', [
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
            ->assertJsonPath('data.events.0.type', 'created');

        $this->getJson('/api/wishes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '希望加入書單功能');
    }

    public function test_anonymous_wish_does_not_keep_a_display_name(): void
    {
        $this->postJson('/api/wishes', [
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

    public function test_open_mode_allows_updates_and_records_status_history(): void
    {
        $wish = $this->createWish();

        $this->patchJson("/api/wishes/{$wish->public_id}", [
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
        $hidden = $this->createWish(['title' => '隱藏願望']);
        $deleted = $this->createWish(['title' => '刪除願望']);

        $this->patchJson("/api/wishes/{$hidden->public_id}", [
            'moderation_status' => 'hidden',
        ])->assertOk();

        $this->deleteJson("/api/wishes/{$deleted->public_id}")
            ->assertOk();

        $this->getJson('/api/wishes')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/wish-management')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->postJson("/api/wish-management/{$deleted->public_id}/restore")
            ->assertOk()
            ->assertJsonPath('data.is_deleted', false);

        $this->assertDatabaseHas('wish_events', [
            'wish_id' => $deleted->id,
            'event_type' => 'restored',
        ]);
    }

    public function test_guest_name_and_supported_values_are_validated(): void
    {
        $this->postJson('/api/wishes', [
            'title' => '缺少名字',
            'category' => 'unknown',
            'author_type' => 'guest',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['category', 'author_name']);
    }

    public function test_switching_from_anonymous_to_guest_requires_a_name(): void
    {
        $wish = $this->createWish([
            'author_type' => 'anonymous',
            'author_name' => null,
        ]);

        $this->patchJson("/api/wishes/{$wish->public_id}", [
            'author_type' => 'guest',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['author_name']);
    }

    public function test_restricted_mode_fails_closed_until_identity_rules_are_implemented(): void
    {
        config()->set('wishes.access_mode', 'restricted');

        $this->getJson('/api/wishes')
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', '沒有權限執行此操作');
    }

    private function createWish(array $attributes = []): Wish
    {
        return Wish::query()->create(array_merge([
            'public_id' => (string) str()->ulid(),
            'title' => '測試願望',
            'description' => '測試內容',
            'category' => 'feature',
            'status' => 'submitted',
            'moderation_status' => 'approved',
            'visibility' => 'public',
            'author_type' => 'guest',
            'author_name' => '測試者',
        ], $attributes));
    }
}
