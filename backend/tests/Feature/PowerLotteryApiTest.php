<?php

namespace Tests\Feature;

use Tests\TestCase;

class PowerLotteryApiTest extends TestCase
{
    public function test_it_generates_the_requested_number_of_valid_draws(): void
    {
        $response = $this->postJson('/api/lottery/power/generate', ['count' => 25]);

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
            $this->postJson('/api/lottery/power/generate', ['count' => $count])
                ->assertUnprocessable()
                ->assertJsonPath('success', false)
                ->assertJsonValidationErrors('count');
        }
    }

    private function assertBetween(int $minimum, int $maximum, int $actual): void
    {
        $this->assertGreaterThanOrEqual($minimum, $actual);
        $this->assertLessThanOrEqual($maximum, $actual);
    }
}
