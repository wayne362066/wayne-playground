<?php

namespace App\Modules\Lottery\Services;

use App\Modules\Lottery\Events\DuelLobbyChanged;
use App\Modules\Lottery\Events\DuelRoomChanged;
use App\Modules\Lottery\Exceptions\DuelException;
use App\Modules\Lottery\Jobs\ResolvePowerLotteryDuel;
use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class DuelRoomService
{
    private const ROOM_PREFIX = 'lottery:duel:room:';

    private const PARTICIPANT_PREFIX = 'lottery:duel:participant-room:';

    private const ROOM_INDEX = 'lottery:duel:rooms';

    public function __construct(
        private readonly DuelParticipantService $participants,
    ) {}

    /** @return array<int, array<string, mixed>> */
    public function publicRooms(): array
    {
        $this->cleanup();
        $rooms = [];

        foreach ($this->roomIds() as $roomId) {
            $room = $this->find($roomId);

            if ($room && $room['status'] === 'waiting' && count($this->activeSeats($room)) === 1) {
                $rooms[] = $this->publicSummary($room);
            }
        }

        usort(
            $rooms,
            fn (array $first, array $second): int => $second['created_at'] <=> $first['created_at'],
        );

        return $rooms;
    }

    public function current(Request $request): ?array
    {
        $participantId = $this->participants->id($request, false);

        if (! $participantId) {
            return null;
        }

        $roomId = $this->cache()->get($this->participantKey($participantId));
        $room = is_string($roomId) ? $this->find($roomId) : null;

        if (! $room || ! $this->seatFor($room, $participantId)) {
            $this->cache()->forget($this->participantKey($participantId));

            return null;
        }

        return $this->snapshot($room, $participantId);
    }

    public function create(Request $request, array $data): array
    {
        $participantId = $this->participants->id($request);
        $nickname = $this->participants->nickname($request, $data['nickname'] ?? null);

        return $this->cache()->lock('lottery:duel:participant-lock:'.$participantId, 5)
            ->block(3, function () use ($request, $data, $participantId, $nickname): array {
                $this->ensureParticipantIsFree($participantId);

                $now = now()->timestamp;
                $roomId = Str::lower((string) Str::ulid());
                $room = [
                    'id' => $roomId,
                    'status' => 'waiting',
                    'mode' => $data['mode'],
                    'ticket_count' => (int) $data['ticket_count'],
                    'game_number' => 0,
                    'version' => 1,
                    'created_at' => $now,
                    'last_action_at' => $now,
                    'players' => [
                        'seat_1' => $this->newPlayer(
                            $participantId,
                            $nickname,
                            $request->user()?->getAuthIdentifier(),
                            $now,
                        ),
                        'seat_2' => null,
                    ],
                    'result' => null,
                    'rematch_votes' => [],
                ];

                $this->persist($room);
                $this->addRoomId($roomId);
                $this->mapParticipant($participantId, $room);
                $this->announce($room, 'created', true);

                return $this->snapshot($room, $participantId);
            });
    }

    public function join(Request $request, string $roomId, array $data): array
    {
        $participantId = $this->participants->id($request);
        $nickname = $this->participants->nickname($request, $data['nickname'] ?? null);

        return $this->cache()->lock('lottery:duel:participant-lock:'.$participantId, 5)
            ->block(3, function () use ($request, $roomId, $participantId, $nickname): array {
                $this->ensureParticipantIsFree($participantId, $roomId);

                return $this->withRoomLock($roomId, function (array $room) use (
                    $request,
                    $participantId,
                    $nickname,
                ): array {
                    if ($seat = $this->seatFor($room, $participantId)) {
                        return $this->snapshot($room, $participantId);
                    }

                    if ($room['status'] !== 'waiting' || count($this->activeSeats($room)) !== 1) {
                        throw new DuelException('這個房間已無法加入。');
                    }

                    $now = now()->timestamp;
                    $room['players']['seat_2'] = $this->newPlayer(
                        $participantId,
                        $nickname,
                        $request->user()?->getAuthIdentifier(),
                        $now,
                    );
                    $room['status'] = 'ready';
                    $this->touch($room, $now);
                    $this->persist($room);
                    $this->mapParticipant($participantId, $room);
                    $this->announce($room, 'player_joined', true);

                    return $this->snapshot($room, $participantId);
                });
            });
    }

    public function show(Request $request, string $roomId): array
    {
        $participantId = $this->participants->id($request, false);
        $room = $this->findOrFail($roomId);

        if (! $participantId || ! $this->seatFor($room, $participantId)) {
            throw new DuelException('你不是這個房間的玩家。', 403);
        }

        return $this->snapshot($room, $participantId);
    }

    public function ready(Request $request, string $roomId): array
    {
        $participantId = $this->requireParticipant($request);
        $job = null;

        $snapshot = $this->withRoomLock($roomId, function (array $room) use (
            $participantId,
            &$job,
        ): array {
            $seat = $this->requireActiveSeat($room, $participantId);

            if (! in_array($room['status'], ['ready'], true)) {
                throw new DuelException('目前不能切換準備狀態。');
            }

            $now = now()->timestamp;
            $room['players'][$seat]['ready'] = true;
            $room['players'][$seat]['connected'] = true;
            $room['players'][$seat]['last_seen_at'] = $now;

            if ($this->bothPlayersReady($room)) {
                $room['status'] = 'running';
                $room['game_number']++;
                $room['result'] = null;
                $room['rematch_votes'] = [];
                $job = [
                    'room_id' => $room['id'],
                    'game_number' => $room['game_number'],
                ];
            }

            $this->touch($room, $now);
            $this->persist($room);
            $this->announce($room, $job ? 'started' : 'player_ready', true);

            return $this->snapshot($room, $participantId);
        });

        if ($job) {
            ResolvePowerLotteryDuel::dispatch($job['room_id'], $job['game_number']);
        }

        return $snapshot;
    }

    public function heartbeat(Request $request, string $roomId): array
    {
        $participantId = $this->requireParticipant($request);

        return $this->withRoomLock($roomId, function (array $room) use ($participantId): array {
            $seat = $this->requireSeat($room, $participantId);
            $wasDisconnected = ! $room['players'][$seat]['connected'];
            $room['players'][$seat]['connected'] = true;
            $room['players'][$seat]['last_seen_at'] = now()->timestamp;

            if ($wasDisconnected) {
                $room['version']++;
            }

            $this->persist($room);

            if ($wasDisconnected) {
                $this->announce($room, 'player_reconnected');
            }

            return $this->snapshot($room, $participantId);
        });
    }

    public function leave(Request $request, string $roomId): ?array
    {
        $participantId = $this->requireParticipant($request);
        $closed = false;

        $snapshot = $this->withRoomLock($roomId, function (array $room) use (
            $participantId,
            &$closed,
        ): ?array {
            $seat = $this->requireSeat($room, $participantId);

            if ($room['status'] === 'finished') {
                $closed = true;
                $this->close($room, 'player_left');

                return null;
            }

            $room['players'][$seat]['active'] = false;
            $room['players'][$seat]['connected'] = false;
            $room['players'][$seat]['ready'] = false;
            $room['players'][$seat]['recoverable'] = false;
            $this->cache()->forget($this->participantKey($participantId));

            if ($room['status'] === 'running') {
                $opponent = $this->opponentSeat($seat);
                $winner = $room['players'][$opponent]['active'] ? $opponent : 'draw';
                $this->finishForfeit($room, $winner, 'player_left');
            } else {
                $this->resetWaitingRoom($room);
            }

            if (count($this->activeSeats($room)) === 0) {
                $closed = true;
                $this->close($room, 'empty');

                return null;
            }

            $this->touch($room);
            $this->persist($room);
            $this->announce($room, 'player_left', true);

            return $this->snapshot($room, $participantId);
        });

        return $closed ? null : $snapshot;
    }

    public function rematch(Request $request, string $roomId): array
    {
        $participantId = $this->requireParticipant($request);

        return $this->withRoomLock($roomId, function (array $room) use ($participantId): array {
            $seat = $this->requireActiveSeat($room, $participantId);

            if ($room['status'] !== 'finished' || count($this->activeSeats($room)) !== 2) {
                throw new DuelException('目前不能提出再來一局。');
            }

            $room['rematch_votes'][$seat] = true;

            if (count($room['rematch_votes']) === 2) {
                $room['status'] = 'ready';
                $room['result'] = null;
                $room['rematch_votes'] = [];
                foreach (['seat_1', 'seat_2'] as $playerSeat) {
                    $room['players'][$playerSeat]['ready'] = false;
                }
            }

            $this->touch($room);
            $this->persist($room);
            $this->announce($room, 'rematch_updated', true);

            return $this->snapshot($room, $participantId);
        });
    }

    public function roomForResolution(string $roomId, int $gameNumber): ?array
    {
        $room = $this->find($roomId);

        if (! $room || $room['status'] !== 'running' || $room['game_number'] !== $gameNumber) {
            return null;
        }

        return $room;
    }

    public function complete(string $roomId, int $gameNumber, array $result): void
    {
        $this->withRoomLock($roomId, function (array $room) use ($gameNumber, $result): void {
            if ($room['status'] !== 'running' || $room['game_number'] !== $gameNumber) {
                return;
            }

            $room['status'] = 'finished';
            $room['result'] = $result;
            $this->touch($room);
            $this->persist($room);
            $this->announce($room, 'finished', true);
        }, false);
    }

    public function presenceMember(string $roomId, string $participantId): array|false
    {
        $room = $this->find($roomId);
        $seat = $room ? $this->seatFor($room, $participantId) : null;

        if (! $seat || ! (
            $room['players'][$seat]['active']
            || $room['players'][$seat]['recoverable']
        )) {
            return false;
        }

        return [
            'id' => $participantId,
            'name' => $room['players'][$seat]['nickname'],
            'seat' => $seat,
        ];
    }

    public function cleanup(?int $now = null): void
    {
        $now ??= now()->timestamp;

        foreach ($this->roomIds() as $roomId) {
            if (! $this->find($roomId)) {
                $this->removeRoomId($roomId);

                continue;
            }

            $this->withRoomLock($roomId, function (array $room) use ($now): void {
                if (($now - $room['last_action_at']) >= config('lottery_duels.room_idle_seconds')) {
                    $this->close($room, 'idle_timeout');

                    return;
                }

                $activeSeats = $this->activeSeats($room);
                $staleSeats = array_values(array_filter(
                    $activeSeats,
                    fn (string $seat): bool => ($now - $room['players'][$seat]['last_seen_at'])
                        >= config('lottery_duels.disconnect_forfeit_seconds'),
                ));

                if ($staleSeats && count($staleSeats) === count($activeSeats)) {
                    foreach ($staleSeats as $seat) {
                        $room['players'][$seat]['active'] = false;
                        $room['players'][$seat]['connected'] = false;
                    }
                    $this->close($room, 'empty');

                    return;
                }

                $changed = false;
                foreach ($staleSeats as $seat) {
                    $room['players'][$seat]['active'] = false;
                    $room['players'][$seat]['connected'] = false;
                    $changed = true;

                    if ($room['status'] === 'running') {
                        $this->finishForfeit(
                            $room,
                            $this->opponentSeat($seat),
                            'disconnect_timeout',
                        );
                    } elseif ($room['status'] !== 'finished') {
                        $this->resetWaitingRoom($room);
                    }
                }

                foreach ($this->activeSeats($room) as $seat) {
                    $isDisconnected = ($now - $room['players'][$seat]['last_seen_at'])
                        >= config('lottery_duels.disconnect_warning_seconds');

                    if ($isDisconnected && $room['players'][$seat]['connected']) {
                        $room['players'][$seat]['connected'] = false;
                        $changed = true;
                    }
                }

                if ($changed) {
                    $room['version']++;
                    $this->persist($room);
                    $this->announce($room, 'connection_updated', true);
                }
            }, false);
        }
    }

    private function finishForfeit(array &$room, string $winner, string $reason): void
    {
        $room['status'] = 'finished';
        $room['result'] = [
            'winner' => $winner,
            'reason' => $reason,
            'players' => [
                'seat_1' => ['outcome' => $winner === 'draw' ? 'draw' : ($winner === 'seat_1' ? 'win' : 'loss')],
                'seat_2' => ['outcome' => $winner === 'draw' ? 'draw' : ($winner === 'seat_2' ? 'win' : 'loss')],
            ],
        ];
        $room['rematch_votes'] = [];
    }

    private function resetWaitingRoom(array &$room): void
    {
        $activePlayers = [];
        foreach ($this->activeSeats($room) as $seat) {
            $player = $room['players'][$seat];
            $player['ready'] = false;
            $activePlayers[] = $player;
        }

        $room['players'] = [
            'seat_1' => $activePlayers[0] ?? null,
            'seat_2' => $activePlayers[1] ?? null,
        ];
        $room['status'] = count($activePlayers) === 2 ? 'ready' : 'waiting';
        $room['result'] = null;
        $room['rematch_votes'] = [];
    }

    private function bothPlayersReady(array $room): bool
    {
        return count($this->activeSeats($room)) === 2
            && $room['players']['seat_1']['ready']
            && $room['players']['seat_2']['ready'];
    }

    private function snapshot(array $room, string $participantId): array
    {
        $players = [];
        foreach (['seat_1', 'seat_2'] as $seat) {
            $player = $room['players'][$seat];
            $players[$seat] = $player ? [
                'seat' => $seat,
                'nickname' => $player['nickname'],
                'ready' => $player['ready'],
                'connected' => $player['connected'],
                'active' => $player['active'],
                'is_member' => $player['user_id'] !== null,
            ] : null;
        }

        return [
            'id' => $room['id'],
            'status' => $room['status'],
            'mode' => $room['mode'],
            'ticket_count' => $room['ticket_count'],
            'game_number' => $room['game_number'],
            'version' => $room['version'],
            'created_at' => date(DATE_ATOM, $room['created_at']),
            'last_action_at' => date(DATE_ATOM, $room['last_action_at']),
            'self_seat' => $this->seatFor($room, $participantId),
            'players' => $players,
            'result' => $room['result'],
            'rematch_votes' => array_keys(array_filter($room['rematch_votes'])),
        ];
    }

    private function publicSummary(array $room): array
    {
        $hostSeat = $this->activeSeats($room)[0];

        return [
            'id' => $room['id'],
            'host_nickname' => $room['players'][$hostSeat]['nickname'],
            'mode' => $room['mode'],
            'ticket_count' => $room['ticket_count'],
            'created_at' => date(DATE_ATOM, $room['created_at']),
        ];
    }

    private function newPlayer(
        string $participantId,
        string $nickname,
        int|string|null $userId,
        int $now,
    ): array {
        return [
            'participant_id' => $participantId,
            'user_id' => $userId,
            'nickname' => $nickname,
            'ready' => false,
            'connected' => true,
            'active' => true,
            'recoverable' => true,
            'last_seen_at' => $now,
        ];
    }

    private function touch(array &$room, ?int $now = null): void
    {
        $room['last_action_at'] = $now ?? now()->timestamp;
        $room['version']++;
    }

    private function persist(array $room): void
    {
        $expiresAt = $room['last_action_at']
            + config('lottery_duels.room_idle_seconds')
            + config('lottery_duels.disconnect_forfeit_seconds')
            + 60;

        $this->cache()->put(
            $this->roomKey($room['id']),
            $room,
            max(1, $expiresAt - now()->timestamp),
        );

        foreach ($room['players'] as $player) {
            if ($player && ($player['active'] || $player['recoverable'])) {
                $this->cache()->put(
                    $this->participantKey($player['participant_id']),
                    $room['id'],
                    max(1, $expiresAt - now()->timestamp),
                );
            }
        }
    }

    private function mapParticipant(string $participantId, array $room): void
    {
        $this->cache()->put(
            $this->participantKey($participantId),
            $room['id'],
            config('lottery_duels.room_idle_seconds') + 120,
        );
    }

    private function ensureParticipantIsFree(
        string $participantId,
        ?string $allowedRoomId = null,
    ): void {
        $roomId = $this->cache()->get($this->participantKey($participantId));

        if (! is_string($roomId)) {
            return;
        }

        $room = $this->find($roomId);
        if (! $room) {
            $this->cache()->forget($this->participantKey($participantId));

            return;
        }

        if (! $this->seatFor($room, $participantId)) {
            $this->cache()->forget($this->participantKey($participantId));

            return;
        }

        if ($allowedRoomId !== $roomId) {
            throw new DuelException('你已經在另一個房間中。');
        }
    }

    private function close(array $room, string $reason): void
    {
        foreach ($room['players'] as $player) {
            if ($player) {
                $this->cache()->forget($this->participantKey($player['participant_id']));
            }
        }

        $this->cache()->forget($this->roomKey($room['id']));
        $this->removeRoomId($room['id']);
        event(new DuelRoomChanged($room['id'], $room['version'] + 1, 'closed:'.$reason));
        event(new DuelLobbyChanged($room['id'], 'closed'));
    }

    private function announce(array $room, string $event, bool $lobbyChanged = false): void
    {
        event(new DuelRoomChanged($room['id'], $room['version'], $event));

        if ($lobbyChanged) {
            event(new DuelLobbyChanged($room['id'], $room['status']));
        }
    }

    private function withRoomLock(
        string $roomId,
        callable $callback,
        bool $failWhenMissing = true,
    ): mixed {
        return $this->cache()->lock('lottery:duel:room-lock:'.$roomId, 10)
            ->block(3, function () use ($roomId, $callback, $failWhenMissing): mixed {
                $room = $this->find($roomId);

                if (! $room) {
                    if ($failWhenMissing) {
                        throw new DuelException('找不到這個房間。', 404);
                    }

                    return null;
                }

                return $callback($room);
            });
    }

    private function findOrFail(string $roomId): array
    {
        return $this->find($roomId)
            ?? throw new DuelException('找不到這個房間。', 404);
    }

    private function find(string $roomId): ?array
    {
        $room = $this->cache()->get($this->roomKey($roomId));

        return is_array($room) ? $room : null;
    }

    /** @return array<int, string> */
    private function activeSeats(array $room): array
    {
        return array_values(array_filter(
            ['seat_1', 'seat_2'],
            fn (string $seat): bool => (bool) ($room['players'][$seat]['active'] ?? false),
        ));
    }

    private function seatFor(array $room, string $participantId): ?string
    {
        foreach (['seat_1', 'seat_2'] as $seat) {
            if (($room['players'][$seat]['participant_id'] ?? null) === $participantId) {
                return $seat;
            }
        }

        return null;
    }

    private function requireParticipant(Request $request): string
    {
        return $this->participants->id($request, false)
            ?? throw new DuelException('找不到目前的玩家身分。', 401);
    }

    private function requireSeat(array $room, string $participantId): string
    {
        return $this->seatFor($room, $participantId)
            ?? throw new DuelException('你不是這個房間的玩家。', 403);
    }

    private function requireActiveSeat(array $room, string $participantId): string
    {
        $seat = $this->requireSeat($room, $participantId);

        if (! $room['players'][$seat]['active']) {
            throw new DuelException('你已經離開這個房間。', 409);
        }

        return $seat;
    }

    private function opponentSeat(string $seat): string
    {
        return $seat === 'seat_1' ? 'seat_2' : 'seat_1';
    }

    /** @return array<int, string> */
    private function roomIds(): array
    {
        $ids = $this->cache()->get(self::ROOM_INDEX, []);

        return is_array($ids) ? array_values(array_unique($ids)) : [];
    }

    private function addRoomId(string $roomId): void
    {
        $this->cache()->lock('lottery:duel:index-lock', 5)->block(3, function () use ($roomId): void {
            $ids = $this->roomIds();
            $ids[] = $roomId;
            $this->cache()->forever(self::ROOM_INDEX, array_values(array_unique($ids)));
        });
    }

    private function removeRoomId(string $roomId): void
    {
        $this->cache()->lock('lottery:duel:index-lock', 5)->block(3, function () use ($roomId): void {
            $ids = array_values(array_filter(
                $this->roomIds(),
                fn (string $id): bool => $id !== $roomId,
            ));
            $this->cache()->forever(self::ROOM_INDEX, $ids);
        });
    }

    private function roomKey(string $roomId): string
    {
        return self::ROOM_PREFIX.$roomId;
    }

    private function participantKey(string $participantId): string
    {
        return self::PARTICIPANT_PREFIX.$participantId;
    }

    private function cache(): Repository
    {
        $store = config('lottery_duels.cache_store');

        return $store ? Cache::store($store) : Cache::store();
    }
}
