<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PowerLotteryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_the_requested_number_of_valid_draws(): void
    {
        $response = $this->withCsrf()
            ->postJson('/api/lottery/power/generate', ['count' => 25]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(25, 'data.draws');

        foreach ($response->json('data.draws') as $draw) {
            $this->assertCount(6, $draw['zone_one']);
            $this->assertCount(6, array_unique($draw['zone_one']));
            $this->assertSame($draw['zone_one'], collect($draw['zone_one'])->sort()->values()->all());

            foreach ($draw['zone_one'] as $number) {
                $this->assertBetween(1, 38, $number);
            }

            $this->assertBetween(1, 8, $draw['zone_two']);
        }
    }

    public function test_count_must_be_an_integer_between_one_and_one_hundred(): void
    {
        foreach ([null, 0, 101, 1.5, 'five'] as $count) {
            $this->withCsrf()
                ->postJson('/api/lottery/power/generate', ['count' => $count])
                ->assertUnprocessable()
                ->assertJsonPath('success', false)
                ->assertJsonValidationErrors('count');
        }
    }

    public function test_it_simulates_a_power_lottery_round(): void
    {
        $response = $this->withCsrf()->postJson('/api/lottery/power/simulate', [
            'ticket_count' => 20,
            'mode' => 'single',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.simulation.mode', 'single')
            ->assertJsonPath('data.simulation.ticket_count', 20)
            ->assertJsonPath('data.simulation.attempts', 1)
            ->assertJsonPath('data.simulation.cost', 2000)
            ->assertJsonCount(6, 'data.simulation.winning_numbers')
            ->assertJsonCount(10, 'data.simulation.prizes');

        $simulation = $response->json('data.simulation');
        $this->assertSame(
            $simulation['total_prize_money'] - $simulation['cost'],
            $simulation['net_profit'],
        );
        $this->assertBetween(1, 8, $simulation['winning_special']);
    }

    public function test_jackpot_mode_returns_a_statistically_sampled_attempt_count(): void
    {
        $response = $this->withCsrf()->postJson('/api/lottery/power/simulate', [
            'ticket_count' => 10000,
            'mode' => 'until_jackpot',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.simulation.completed', true)
            ->assertJsonPath('data.simulation.calculation_method', 'geometric_distribution')
            ->assertJsonPath('data.simulation.total_prize_count', 1)
            ->assertJsonPath('data.simulation.prizes.0.count', 1);

        $this->assertGreaterThanOrEqual(1, $response->json('data.simulation.attempts'));
    }

    public function test_profit_mode_finishes_within_its_ticket_evaluation_budget(): void
    {
        $response = $this->withCsrf()->postJson('/api/lottery/power/simulate', [
            'ticket_count' => 10000,
            'mode' => 'until_profit',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.simulation.mode', 'until_profit')
            ->assertJsonPath('data.simulation.maximum_attempts', 100)
            ->assertJsonPath('data.simulation.calculation_method', 'ticket_simulation');

        $this->assertBetween(1, 100, $response->json('data.simulation.attempts'));
    }

    public function test_simulation_input_must_be_valid(): void
    {
        foreach ([
            ['ticket_count' => 0, 'mode' => 'single', 'field' => 'ticket_count'],
            ['ticket_count' => 10001, 'mode' => 'single', 'field' => 'ticket_count'],
            ['ticket_count' => 10, 'mode' => 'forever', 'field' => 'mode'],
        ] as $payload) {
            $field = $payload['field'];
            unset($payload['field']);

            $this->withCsrf()
                ->postJson('/api/lottery/power/simulate', $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors($field);
        }
    }

    private function assertBetween(int $minimum, int $maximum, int $actual): void
    {
        $this->assertGreaterThanOrEqual($minimum, $actual);
        $this->assertLessThanOrEqual($maximum, $actual);
    }
}
