<?php

namespace Tests\Unit;

use App\Modules\Lottery\Services\PowerLotteryDuelSimulator;
use Tests\TestCase;

class PowerLotteryDuelSimulatorTest extends TestCase
{
    public function test_single_round_uses_the_same_winning_draw_for_both_players(): void
    {
        $result = app(PowerLotteryDuelSimulator::class)->resolve(3, 'single');

        $first = $result['players']['seat_1'];
        $second = $result['players']['seat_2'];

        $this->assertSame($first['winning_numbers'], $second['winning_numbers']);
        $this->assertSame($first['winning_special'], $second['winning_special']);
        $this->assertContains($result['winner'], ['seat_1', 'seat_2', 'draw']);
        $this->assertContains($first['outcome'], ['win', 'loss', 'draw']);
        $this->assertContains($second['outcome'], ['win', 'loss', 'draw']);
    }

    public function test_jackpot_mode_compares_the_first_successful_period(): void
    {
        $result = app(PowerLotteryDuelSimulator::class)->resolve(10000, 'until_jackpot');

        $first = $result['players']['seat_1'];
        $second = $result['players']['seat_2'];

        $this->assertTrue($first['completed']);
        $this->assertTrue($second['completed']);
        $this->assertGreaterThanOrEqual(1, $first['attempts']);
        $this->assertGreaterThanOrEqual(1, $second['attempts']);
    }
}
