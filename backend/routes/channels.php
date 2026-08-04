<?php

use App\Services\DuelRoomService;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'lottery.duel.{roomId}',
    fn ($participant, string $roomId) => app(
        DuelRoomService::class
    )->presenceMember($roomId, (string) $participant->getAuthIdentifier()),
    ['guards' => ['duel']],
);
