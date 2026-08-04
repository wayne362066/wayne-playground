<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;

final class DuelRoomChanged implements ShouldBroadcastNow, ShouldRescue
{
    public function __construct(
        public readonly string $roomId,
        public readonly int $version,
        public readonly string $event,
    ) {}

    public function broadcastOn(): PresenceChannel
    {
        return new PresenceChannel('lottery.duel.'.$this->roomId);
    }

    public function broadcastAs(): string
    {
        return 'lottery.duel.room.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
            'version' => $this->version,
            'event' => $this->event,
        ];
    }
}
