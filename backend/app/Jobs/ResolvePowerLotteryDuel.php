<?php

namespace App\Jobs;

use App\Services\DuelRoomService;
use App\Services\PowerLotteryDuelSimulator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class ResolvePowerLotteryDuel implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 75;

    public function __construct(
        public readonly string $roomId,
        public readonly int $gameNumber,
    ) {}

    public function handle(
        DuelRoomService $rooms,
        PowerLotteryDuelSimulator $simulator,
    ): void {
        $room = $rooms->roomForResolution($this->roomId, $this->gameNumber);

        if (! $room) {
            return;
        }

        $result = $simulator->resolve($room['ticket_count'], $room['mode']);
        $rooms->complete($this->roomId, $this->gameNumber, $result);
    }
}
