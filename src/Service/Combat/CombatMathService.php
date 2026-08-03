<?php

declare(strict_types=1);

namespace App\Service\Combat;

use App\Domain\Constants\FightConstants;
use App\Service\Random\RandomizerInterface;

readonly class CombatMathService
{
    public function __construct(private readonly ?RandomizerInterface $randomizer = null)
    {
    }

    public function calculateDamage(int $strength): int
    {
        $baseDamage = max(1, $strength);
        $randomFactor = $this->randomInt(
            FightConstants::DAMAGE_VARIANCE_MIN,
            FightConstants::DAMAGE_VARIANCE_MAX,
        ) / FightConstants::DAMAGE_VARIANCE_DIVISOR;

        return (int) round($baseDamage * $randomFactor);
    }

    public function dodgeChancePercentForDefender(int $attackerAgility, int $defenderAgility): int
    {
        $total = $attackerAgility + $defenderAgility;
        if ($total <= 0) {
            return FightConstants::EMPTY_STAT_DODGE_OR_CRIT_PERCENT;
        }

        return (int) round(($defenderAgility / $total) * 100);
    }

    public function criticalHitChancePercentForAttacker(int $attackerLuck, int $defenderLuck): int
    {
        $total = $attackerLuck + $defenderLuck;
        if ($total <= 0) {
            return FightConstants::EMPTY_STAT_DODGE_OR_CRIT_PERCENT;
        }

        return (int) round(($attackerLuck / $total) * 100);
    }

    public function mitigateDamageByIntelligence(int $rawDamage, int $defenderIntelligence, int $attackerStrength): int
    {
        if ($rawDamage <= 0) {
            return 0;
        }

        $denominator = $defenderIntelligence + $attackerStrength + FightConstants::MITIGATION_DENOMINATOR_BASE;
        $percent = (int) round(100 * $defenderIntelligence / max(1, $denominator));
        $percent = min(FightConstants::MAX_MITIGATION_PERCENT, max(0, $percent));

        $after = (int) round($rawDamage * (100 - $percent) / 100);

        return max(1, $after);
    }

    public function calculateDamageWithRandomizer(int $strength, RandomizerInterface $randomizer): int
    {
        $baseDamage = max(1, $strength);
        $randomFactor = $randomizer->int(
            FightConstants::DAMAGE_VARIANCE_MIN,
            FightConstants::DAMAGE_VARIANCE_MAX,
        ) / FightConstants::DAMAGE_VARIANCE_DIVISOR;

        return (int) round($baseDamage * $randomFactor);
    }

    private function randomInt(int $min, int $max): int
    {
        if ($this->randomizer !== null) {
            return $this->randomizer->int($min, $max);
        }

        return random_int($min, $max);
    }
}
