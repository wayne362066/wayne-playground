<?php

namespace App\Services;

final class PowerLotteryGenerator
{
    public function generate(int $count): array
    {
        return array_map(
            fn (): array => $this->generateDraw(),
            range(1, $count),
        );
    }

    private function generateDraw(): array
    {
        $numbers = range(1, 38);
        shuffle($numbers);
        $zoneOne = array_slice($numbers, 0, 6);
        sort($zoneOne);

        return [
            'zone_one' => $zoneOne,
            'zone_two' => random_int(1, 8),
        ];
    }
}
