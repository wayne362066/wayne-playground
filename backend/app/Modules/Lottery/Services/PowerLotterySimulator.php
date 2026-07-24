<?php

namespace App\Modules\Lottery\Services;

final class PowerLotterySimulator
{
    private const TICKET_PRICE = 100;

    private const MAX_TICKET_EVALUATIONS = 1_000_000;

    private const JACKPOT_COMBINATIONS = 22_085_448;

    private const PRIZES = [
        'prize_1' => ['label' => '頭獎', 'matches' => 6, 'special' => true, 'amount' => 2_007_288_888],
        'prize_2' => ['label' => '貳獎', 'matches' => 6, 'special' => false, 'amount' => 5_851_027],
        'prize_3' => ['label' => '參獎', 'matches' => 5, 'special' => true, 'amount' => 150_000],
        'prize_4' => ['label' => '肆獎', 'matches' => 5, 'special' => false, 'amount' => 20_000],
        'prize_5' => ['label' => '伍獎', 'matches' => 4, 'special' => true, 'amount' => 4_000],
        'prize_6' => ['label' => '陸獎', 'matches' => 4, 'special' => false, 'amount' => 800],
        'prize_7' => ['label' => '柒獎', 'matches' => 3, 'special' => true, 'amount' => 400],
        'prize_8' => ['label' => '捌獎', 'matches' => 2, 'special' => true, 'amount' => 200],
        'prize_9' => ['label' => '玖獎', 'matches' => 3, 'special' => false, 'amount' => 100],
        'normal' => ['label' => '普獎', 'matches' => 1, 'special' => true, 'amount' => 100],
    ];

    public function simulate(int $ticketCount, string $mode): array
    {
        return match ($mode) {
            'until_profit' => $this->simulateUntilProfit($ticketCount),
            'until_jackpot' => $this->simulateUntilJackpot($ticketCount),
            default => $this->formatRound(
                $this->simulateRound($ticketCount),
                'single',
                $ticketCount,
            ),
        };
    }

    private function simulateUntilProfit(int $ticketCount): array
    {
        $maximumAttempts = max(1, intdiv(self::MAX_TICKET_EVALUATIONS, $ticketCount));
        $round = [];

        for ($attempt = 1; $attempt <= $maximumAttempts; $attempt++) {
            $round = $this->simulateRound($ticketCount);

            if ($round['net_profit'] > 0) {
                return $this->formatRound(
                    $round,
                    'until_profit',
                    $ticketCount,
                    $attempt,
                    true,
                    $maximumAttempts,
                );
            }
        }

        return $this->formatRound(
            $round,
            'until_profit',
            $ticketCount,
            $maximumAttempts,
            false,
            $maximumAttempts,
        );
    }

    private function simulateUntilJackpot(int $ticketCount): array
    {
        $winningNumbers = $this->drawZoneOne();
        $winningSpecial = random_int(1, 8);
        $roundSuccessProbability = 1 - pow(
            1 - (1 / self::JACKPOT_COMBINATIONS),
            $ticketCount,
        );
        $uniformRandom = random_int(1, PHP_INT_MAX - 1) / PHP_INT_MAX;
        $attempts = (int) floor(
            log1p(-$uniformRandom) / log1p(-$roundSuccessProbability),
        ) + 1;

        $counts = array_fill_keys(array_keys(self::PRIZES), 0);
        $counts['prize_1'] = 1;
        $cost = $attempts * $ticketCount * self::TICKET_PRICE;

        return [
            'mode' => 'until_jackpot',
            'ticket_count' => $ticketCount,
            'attempts' => $attempts,
            'losing_rounds' => $attempts - 1,
            'completed' => true,
            'winning_numbers' => $winningNumbers,
            'winning_special' => $winningSpecial,
            'prizes' => $this->formatPrizes($counts),
            'total_prize_count' => 1,
            'total_prize_money' => self::PRIZES['prize_1']['amount'],
            'cost' => $cost,
            'net_profit' => self::PRIZES['prize_1']['amount'] - $cost,
            'message' => sprintf('第 %s 期模擬出頭獎。', number_format($attempts)),
            'calculation_method' => 'geometric_distribution',
        ];
    }

    private function simulateRound(int $ticketCount): array
    {
        $winningNumbers = $this->drawZoneOne();
        $winningSpecial = random_int(1, 8);
        $counts = array_fill_keys(array_keys(self::PRIZES), 0);

        for ($ticket = 0; $ticket < $ticketCount; $ticket++) {
            $ticketNumbers = $this->drawZoneOne();
            $ticketSpecial = random_int(1, 8);
            $matchCount = count(array_intersect($ticketNumbers, $winningNumbers));
            $specialMatch = $ticketSpecial === $winningSpecial;
            $prizeKey = $this->resolvePrize($matchCount, $specialMatch);

            if ($prizeKey !== null) {
                $counts[$prizeKey]++;
            }
        }

        $totalPrizeMoney = 0;
        foreach (self::PRIZES as $key => $prize) {
            $totalPrizeMoney += $counts[$key] * $prize['amount'];
        }

        $cost = $ticketCount * self::TICKET_PRICE;

        return [
            'winning_numbers' => $winningNumbers,
            'winning_special' => $winningSpecial,
            'counts' => $counts,
            'total_prize_count' => array_sum($counts),
            'total_prize_money' => $totalPrizeMoney,
            'cost' => $cost,
            'net_profit' => $totalPrizeMoney - $cost,
        ];
    }

    private function formatRound(
        array $round,
        string $mode,
        int $ticketCount,
        int $attempts = 1,
        bool $completed = true,
        ?int $maximumAttempts = null,
    ): array {
        $message = $round['total_prize_count'] > 0
            ? sprintf('本期共中 %d 個獎。', $round['total_prize_count'])
            : '本期未中獎，再接再厲。';

        if ($mode === 'until_profit') {
            $message = $completed
                ? sprintf('第 %s 期出現單期獲利。', number_format($attempts))
                : sprintf('已模擬 %s 期，尚未出現單期獲利。', number_format($attempts));
        }

        return [
            'mode' => $mode,
            'ticket_count' => $ticketCount,
            'attempts' => $attempts,
            'losing_rounds' => $mode === 'until_profit' ? max(0, $attempts - 1) : 0,
            'completed' => $completed,
            'maximum_attempts' => $maximumAttempts,
            'winning_numbers' => $round['winning_numbers'],
            'winning_special' => $round['winning_special'],
            'prizes' => $this->formatPrizes($round['counts']),
            'total_prize_count' => $round['total_prize_count'],
            'total_prize_money' => $round['total_prize_money'],
            'cost' => $round['cost'],
            'net_profit' => $round['net_profit'],
            'message' => $message,
            'calculation_method' => 'ticket_simulation',
        ];
    }

    private function formatPrizes(array $counts): array
    {
        $result = [];

        foreach (self::PRIZES as $key => $prize) {
            $count = $counts[$key];
            $result[] = [
                'key' => $key,
                'label' => $prize['label'],
                'count' => $count,
                'unit_prize' => $prize['amount'],
                'amount' => $count * $prize['amount'],
            ];
        }

        return $result;
    }

    private function resolvePrize(int $matchCount, bool $specialMatch): ?string
    {
        foreach (self::PRIZES as $key => $prize) {
            if ($prize['matches'] === $matchCount && $prize['special'] === $specialMatch) {
                return $key;
            }
        }

        return null;
    }

    private function drawZoneOne(): array
    {
        $numbers = range(1, 38);
        shuffle($numbers);
        $draw = array_slice($numbers, 0, 6);
        sort($draw);

        return $draw;
    }
}
