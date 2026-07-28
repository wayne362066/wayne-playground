<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'lottery.duel.{roomId}',
    fn ($participant, string $roomId) => app(
        \App\Modules\Lottery\Services\DuelRoomService::class
    )->presenceMember($roomId, (string) $participant->getAuthIdentifier()),
    ['guards' => ['duel']],
);
