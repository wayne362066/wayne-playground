<?php

namespace App\Services;

final class PowerLotteryDuelSimulator
{
    private const MAX_TICKET_EVALUATIONS = 1_000_000;

    public function __construct(
        private readonly PowerLotterySimulator $simulator,
    ) {}

    public function resolve(int $ticketCount, string $mode): array
    {
        $results = match ($mode) {
            'until_profit' => $this->untilProfit($ticketCount),
            'until_jackpot' => [
                'seat_1' => $this->simulator->simulate($ticketCount, 'until_jackpot'),
                'seat_2' => $this->simulator->simulate($ticketCount, 'until_jackpot'),
            ],
            default => $this->singleRound($ticketCount),
        };

        $winner = $this->winner($results, $mode);

        foreach ($results as $seat => &$result) {
            $result['outcome'] = $winner === 'draw'
                ? 'draw'
                : ($winner === $seat ? 'win' : 'loss');
        }
        unset($result);

        return [
            'winner' => $winner,
            'reason' => 'simulation',
            'players' => $results,
        ];
    }

    private function singleRound(int $ticketCount): array
    {
        $draw = $this->simulator->drawWinningDraw();

        return [
            'seat_1' => $this->simulator->simulateRoundForDraw(
                $ticketCount,
                $draw['numbers'],
                $draw['special'],
            ),
            'seat_2' => $this->simulator->simulateRoundForDraw(
                $ticketCount,
                $draw['numbers'],
                $draw['special'],
            ),
        ];
    }

    private function untilProfit(int $ticketCount): array
    {
        $maximumAttempts = max(1, intdiv(self::MAX_TICKET_EVALUATIONS, $ticketCount));
        $results = [];
        $lastRounds = [];

        for ($attempt = 1; $attempt <= $maximumAttempts; $attempt++) {
            $draw = $this->simulator->drawWinningDraw();

            foreach (['seat_1', 'seat_2'] as $seat) {
                if (isset($results[$seat])) {
                    continue;
                }

                $round = $this->simulator->simulateRoundForDraw(
                    $ticketCount,
                    $draw['numbers'],
                    $draw['special'],
                );
                $lastRounds[$seat] = $round;

                if ($round['net_profit'] > 0) {
                    $results[$seat] = $this->profitResult(
                        $round,
                        $attempt,
                        $maximumAttempts,
                        true,
                    );
                }
            }

            if (count($results) === 2) {
                break;
            }
        }

        foreach (['seat_1', 'seat_2'] as $seat) {
            $results[$seat] ??= $this->profitResult(
                $lastRounds[$seat],
                $maximumAttempts,
                $maximumAttempts,
                false,
            );
        }

        return $results;
    }

    private function profitResult(
        array $round,
        int $attempts,
        int $maximumAttempts,
        bool $completed,
    ): array {
        return [
            ...$round,
            'mode' => 'until_profit',
            'attempts' => $attempts,
            'losing_rounds' => $completed ? max(0, $attempts - 1) : $attempts,
            'completed' => $completed,
            'maximum_attempts' => $maximumAttempts,
            'message' => $completed
                ? sprintf('第 %s 期出現單期獲利。', number_format($attempts))
                : sprintf('已模擬 %s 期，仍未出現單期獲利。', number_format($attempts)),
        ];
    }

    private function winner(array $results, string $mode): string
    {
        $first = $results['seat_1'];
        $second = $results['seat_2'];

        if ($mode === 'single') {
            return $this->compareHigher(
                $first['total_prize_money'],
                $second['total_prize_money'],
            );
        }

        if ($mode === 'until_profit') {
            if ($first['completed'] !== $second['completed']) {
                return $first['completed'] ? 'seat_1' : 'seat_2';
            }

            if (! $first['completed']) {
                return 'draw';
            }
        }

        return $this->compareLower($first['attempts'], $second['attempts']);
    }

    private function compareHigher(int $first, int $second): string
    {
        return match (true) {
            $first > $second => 'seat_1',
            $second > $first => 'seat_2',
            default => 'draw',
        };
    }

    private function compareLower(int $first, int $second): string
    {
        return match (true) {
            $first < $second => 'seat_1',
            $second < $first => 'seat_2',
            default => 'draw',
        };
    }
}
