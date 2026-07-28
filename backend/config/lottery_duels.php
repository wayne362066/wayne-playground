<?php

return [
    'cache_store' => env('LOTTERY_DUEL_CACHE_STORE'),
    'room_idle_seconds' => (int) env('LOTTERY_DUEL_ROOM_IDLE_SECONDS', 1800),
    'disconnect_warning_seconds' => (int) env('LOTTERY_DUEL_DISCONNECT_WARNING_SECONDS', 7),
    'disconnect_forfeit_seconds' => (int) env('LOTTERY_DUEL_DISCONNECT_FORFEIT_SECONDS', 15),
    'heartbeat_seconds' => (int) env('LOTTERY_DUEL_HEARTBEAT_SECONDS', 5),
];
