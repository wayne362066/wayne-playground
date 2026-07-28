<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(
    fn () => app(\App\Modules\Lottery\Services\DuelRoomService::class)->cleanup()
)->name('lottery-duels:cleanup')
    ->everyFiveSeconds()
    ->withoutOverlapping();
