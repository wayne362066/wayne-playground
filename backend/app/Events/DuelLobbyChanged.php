<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;

final class DuelLobbyChanged implements ShouldBroadcastNow, ShouldRescue
{
    public function __construct(
        public readonly string $roomId,
        public readonly string $action,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('lottery.duel.lobby');
    }

    public function broadcastAs(): string
    {
        return 'lottery.duel.lobby.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
            'action' => $this->action,
        ];
    }
}
