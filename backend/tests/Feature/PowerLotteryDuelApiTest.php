<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Access\Models\Role;
use App\Modules\Lottery\Jobs\ResolvePowerLotteryDuel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PowerLotteryDuelApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_guests_can_create_and_join_a_public_room(): void
    {
        $room = $this->asGuest('host')
            ->postJson('/api/lottery/duels', [
                'nickname' => '房主',
                'ticket_count' => 25,
                'mode' => 'single',
            ])
            ->assertCreated()
            ->assertJsonPath('data.room.status', 'waiting')
            ->assertJsonPath('data.room.self_seat', 'seat_1')
            ->json('data.room');

        $this->asGuest('visitor')
            ->getJson('/api/lottery/duels')
            ->assertOk()
            ->assertJsonCount(1, 'data.rooms')
            ->assertJsonPath('data.rooms.0.host_nickname', '房主');

        $this->asGuest('guest')
            ->postJson("/api/lottery/duels/{$room['id']}/join", [
                'nickname' => '挑戰者',
            ])
            ->assertOk()
            ->assertJsonPath('data.room.status', 'ready')
            ->assertJsonPath('data.room.self_seat', 'seat_2')
            ->assertJsonPath('data.room.players.seat_1.nickname', '房主')
            ->assertJsonPath('data.room.players.seat_2.nickname', '挑戰者');

        $this->asGuest('visitor')
            ->getJson('/api/lottery/duels')
            ->assertOk()
            ->assertJsonCount(0, 'data.rooms');
    }

    public function test_logged_in_player_uses_their_account_display_name(): void
    {
        $user = User::factory()->create([
            'username' => 'wayne',
            'nickname' => '小維',
        ]);
        $user->roles()->attach(Role::query()->where('key', 'member')->valueOrFail('id'));

        $this->actingAs($user)
            ->withCsrf()
            ->postJson('/api/lottery/duels', [
                'ticket_count' => 5,
                'mode' => 'single',
            ])
            ->assertCreated()
            ->assertJsonPath('data.room.players.seat_1.nickname', '小維')
            ->assertJsonPath('data.room.players.seat_1.is_member', true);
    }

    public function test_both_players_must_be_ready_before_the_game_is_queued(): void
    {
        Queue::fake();
        $room = $this->createJoinedRoom();

        $this->asGuest('host')
            ->postJson("/api/lottery/duels/{$room['id']}/ready")
            ->assertOk()
            ->assertJsonPath('data.room.status', 'ready')
            ->assertJsonPath('data.room.players.seat_1.ready', true);

        Queue::assertNothingPushed();

        $this->asGuest('guest')
            ->postJson("/api/lottery/duels/{$room['id']}/ready")
            ->assertOk()
            ->assertJsonPath('data.room.status', 'running')
            ->assertJsonPath('data.room.game_number', 1);

        Queue::assertPushed(
            ResolvePowerLotteryDuel::class,
            fn (ResolvePowerLotteryDuel $job): bool => $job->roomId === $room['id']
                && $job->gameNumber === 1,
        );
    }

    public function test_current_room_restores_the_same_guest_session(): void
    {
        $room = $this->asGuest('host')
            ->postJson('/api/lottery/duels', [
                'nickname' => '回房玩家',
                'ticket_count' => 5,
                'mode' => 'until_jackpot',
            ])
            ->json('data.room');

        $this->asGuest('host')
            ->getJson('/api/lottery/duels/current')
            ->assertOk()
            ->assertJsonPath('data.room.id', $room['id'])
            ->assertJsonPath('data.room.self_seat', 'seat_1');

        $this->asGuest('other')
            ->getJson('/api/lottery/duels/current')
            ->assertOk()
            ->assertJsonPath('data.room', null);
    }

    public function test_leaving_an_active_game_awards_the_opponent(): void
    {
        Queue::fake();
        $room = $this->createJoinedRoom();

        $this->asGuest('host')->postJson("/api/lottery/duels/{$room['id']}/ready");
        $this->asGuest('guest')->postJson("/api/lottery/duels/{$room['id']}/ready");

        $this->asGuest('host')
            ->postJson("/api/lottery/duels/{$room['id']}/leave")
            ->assertOk()
            ->assertJsonPath('data.room.status', 'finished')
            ->assertJsonPath('data.room.result.winner', 'seat_2')
            ->assertJsonPath('data.room.result.reason', 'player_left');

        $this->asGuest('host')
            ->getJson('/api/lottery/duels/current')
            ->assertOk()
            ->assertJsonPath('data.room', null);
    }

    public function test_both_players_must_accept_a_rematch_and_ready_again(): void
    {
        Queue::fake();
        $room = $this->createJoinedRoom();

        $this->asGuest('host')->postJson("/api/lottery/duels/{$room['id']}/ready");
        $this->asGuest('guest')->postJson("/api/lottery/duels/{$room['id']}/ready");
        app(\App\Modules\Lottery\Services\DuelRoomService::class)->complete(
            $room['id'],
            1,
            [
                'winner' => 'draw',
                'reason' => 'simulation',
                'players' => [
                    'seat_1' => ['outcome' => 'draw'],
                    'seat_2' => ['outcome' => 'draw'],
                ],
            ],
        );

        $this->asGuest('host')
            ->postJson("/api/lottery/duels/{$room['id']}/rematch")
            ->assertOk()
            ->assertJsonPath('data.room.status', 'finished')
            ->assertJsonPath('data.room.rematch_votes.0', 'seat_1');

        $this->asGuest('guest')
            ->postJson("/api/lottery/duels/{$room['id']}/rematch")
            ->assertOk()
            ->assertJsonPath('data.room.status', 'ready')
            ->assertJsonPath('data.room.players.seat_1.ready', false)
            ->assertJsonPath('data.room.players.seat_2.ready', false)
            ->assertJsonCount(0, 'data.room.rematch_votes');
    }

    public function test_room_closes_when_its_only_player_leaves(): void
    {
        $room = $this->asGuest('host')
            ->postJson('/api/lottery/duels', [
                'nickname' => '房主',
                'ticket_count' => 10,
                'mode' => 'single',
            ])
            ->json('data.room');

        $this->asGuest('host')
            ->postJson("/api/lottery/duels/{$room['id']}/leave")
            ->assertOk()
            ->assertJsonPath('data.room', null);

        $this->asGuest('host')
            ->getJson('/api/lottery/duels/current')
            ->assertOk()
            ->assertJsonPath('data.room', null);
    }

    public function test_disconnect_timeout_forfeits_an_active_game_after_fifteen_seconds(): void
    {
        Queue::fake();
        $this->freezeTime();
        $room = $this->createJoinedRoom();

        $this->asGuest('host')->postJson("/api/lottery/duels/{$room['id']}/ready");
        $this->asGuest('guest')->postJson("/api/lottery/duels/{$room['id']}/ready");

        $this->travel(10)->seconds();
        $this->asGuest('host')->postJson("/api/lottery/duels/{$room['id']}/heartbeat");
        $this->travel(6)->seconds();
        app(\App\Modules\Lottery\Services\DuelRoomService::class)->cleanup();

        $this->asGuest('host')
            ->getJson("/api/lottery/duels/{$room['id']}")
            ->assertOk()
            ->assertJsonPath('data.room.status', 'finished')
            ->assertJsonPath('data.room.result.winner', 'seat_1')
            ->assertJsonPath('data.room.result.reason', 'disconnect_timeout')
            ->assertJsonPath('data.room.players.seat_2.active', false);

        $this->asGuest('guest')
            ->getJson('/api/lottery/duels/current')
            ->assertOk()
            ->assertJsonPath('data.room.id', $room['id'])
            ->assertJsonPath('data.room.result.winner', 'seat_1');
    }

    public function test_a_waiting_room_closes_when_its_only_player_disconnects(): void
    {
        $this->freezeTime();
        $room = $this->asGuest('host')
            ->postJson('/api/lottery/duels', [
                'nickname' => '房主',
                'ticket_count' => 10,
                'mode' => 'single',
            ])
            ->json('data.room');

        $this->travel(16)->seconds();
        app(\App\Modules\Lottery\Services\DuelRoomService::class)->cleanup();

        $this->asGuest('host')
            ->getJson('/api/lottery/duels/current')
            ->assertOk()
            ->assertJsonPath('data.room', null);

        $this->asGuest('visitor')
            ->getJson('/api/lottery/duels')
            ->assertOk()
            ->assertJsonCount(0, 'data.rooms');
    }

    public function test_heartbeat_does_not_extend_the_thirty_minute_room_activity_limit(): void
    {
        $this->freezeTime();
        $room = $this->asGuest('host')
            ->postJson('/api/lottery/duels', [
                'nickname' => '房主',
                'ticket_count' => 10,
                'mode' => 'single',
            ])
            ->json('data.room');

        $this->travel(1790)->seconds();
        $this->asGuest('host')
            ->postJson("/api/lottery/duels/{$room['id']}/heartbeat")
            ->assertOk();
        $this->travel(11)->seconds();
        app(\App\Modules\Lottery\Services\DuelRoomService::class)->cleanup();

        $this->asGuest('host')
            ->getJson('/api/lottery/duels/current')
            ->assertOk()
            ->assertJsonPath('data.room', null);
    }

    public function test_guest_nickname_and_room_configuration_are_validated(): void
    {
        $this->asGuest('host')
            ->postJson('/api/lottery/duels', [
                'ticket_count' => 10001,
                'mode' => 'unknown',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nickname', 'ticket_count', 'mode']);
    }

    private function createJoinedRoom(): array
    {
        $room = $this->asGuest('host')
            ->postJson('/api/lottery/duels', [
                'nickname' => '房主',
                'ticket_count' => 1,
                'mode' => 'single',
            ])
            ->json('data.room');

        return $this->asGuest('guest')
            ->postJson("/api/lottery/duels/{$room['id']}/join", [
                'nickname' => '挑戰者',
            ])
            ->json('data.room');
    }

    private function asGuest(string $id): static
    {
        return $this
            ->withSession([
                '_token' => 'test-csrf-token',
                'lottery_duel.participant_id' => 'guest:'.$id,
            ])
            ->withHeader('X-CSRF-TOKEN', 'test-csrf-token');
    }
}
