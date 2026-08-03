<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Domain\Constants\MissionConstants;

final class MissionEconomyRoller
{
    /**
     * @return array{gold: int, exp: int, energy: int}
     */
    public function roll(int $durationSeconds, int $playerLevel): array
    {
        $minutes = max(1, (int) ($durationSeconds / MissionConstants::SECONDS_PER_MINUTE));
        $levelScale = PlayerLevelScale::factor($playerLevel);
        $longBonus = $minutes >= MissionConstants::LONG_MISSION_MINUTES
            ? MissionConstants::LONG_MISSION_REWARD_BONUS
            : 1.0;

        $gold = (int) max(1, round(
            $minutes * random_int(MissionConstants::GOLD_PER_MINUTE_MIN, MissionConstants::GOLD_PER_MINUTE_MAX) * $levelScale * $longBonus
        ));
        $exp = (int) max(1, round(
            $minutes * random_int(MissionConstants::EXP_PER_MINUTE_MIN, MissionConstants::EXP_PER_MINUTE_MAX) * $levelScale * $longBonus
        ));

        return [
            'gold' => $gold,
            'exp' => $exp,
            'energy' => $this->rollEnergyCost($durationSeconds),
        ];
    }

    private function rollEnergyCost(int $durationSeconds): int
    {
        $range = MissionConstants::ENERGY_BY_DURATION_SECONDS[$durationSeconds]
            ?? [
                max(1, (int) ceil($durationSeconds / MissionConstants::ENERGY_FALLBACK_LOW_DIVISOR)),
                max(1, (int) ceil($durationSeconds / MissionConstants::ENERGY_FALLBACK_HIGH_DIVISOR)),
            ];

        return random_int($range[0], $range[1]);
    }
}
