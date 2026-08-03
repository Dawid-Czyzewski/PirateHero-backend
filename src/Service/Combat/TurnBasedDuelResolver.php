<?php

declare(strict_types=1);

namespace App\Service\Combat;

use App\Domain\Constants\FightConstants;
use App\Enum\FightMoveResult;
use App\Service\Random\RandomizerInterface;

class TurnBasedDuelResolver
{
    private const MAX_MOVES = 400;

    public function __construct(
        private readonly CombatMathService $combatMath,
        private readonly ?RandomizerInterface $randomizer = null,
    ) {
    }

    /**
     * @param array<string, int> $rawAttackerStats
     * @param array<string, int> $rawDefenderStats
     *
     * @return list<array{
     *     moveNumber: int,
     *     isAttackerTurn: bool,
     *     result: FightMoveResult,
     *     damage: int,
     *     attackerHealthAfter: int,
     *     defenderHealthAfter: int
     * }>
     */
    public function resolve(
        int $attackerMaxHp,
        int $defenderMaxHp,
        array $rawAttackerStats,
        array $rawDefenderStats,
    ): array {
        $attacker = CombatStatsNormalizer::forCombat($rawAttackerStats);
        $defender = CombatStatsNormalizer::forCombat($rawDefenderStats);

        $attackerHp = $attackerMaxHp;
        $defenderHp = $defenderMaxHp;

        $attackerStarts = $this->resolveFirstStrike($attacker['agility'], $defender['agility']);

        $moves = [];
        $moveNumber = 1;

        while ($attackerHp > 0 && $defenderHp > 0 && $moveNumber <= self::MAX_MOVES) {
            $isAttackerTurn = $this->isAttackerTurn($moveNumber, $attackerStarts);
            $striker = $isAttackerTurn ? $attacker : $defender;
            $target = $isAttackerTurn ? $defender : $attacker;

            $move = $this->resolveSingleHit(
                $moveNumber,
                $isAttackerTurn,
                $striker,
                $target,
                $attackerHp,
                $defenderHp,
            );

            $moves[] = $move;
            $attackerHp = $move['attackerHealthAfter'];
            $defenderHp = $move['defenderHealthAfter'];
            ++$moveNumber;
        }

        return $moves;
    }

    private function resolveFirstStrike(int $attackerAgility, int $defenderAgility): bool
    {
        if ($attackerAgility > $defenderAgility) {
            return true;
        }
        if ($attackerAgility < $defenderAgility) {
            return false;
        }

        return $this->rollInt(0, 1) === 1;
    }

    private function isAttackerTurn(int $moveNumber, bool $attackerStarts): bool
    {
        return ($moveNumber % 2 === 1) ? $attackerStarts : !$attackerStarts;
    }

    /**
     * @param array{strength: int, agility: int, luck: int, intelligence: int, health: int} $striker
     * @param array{strength: int, agility: int, luck: int, intelligence: int, health: int} $target
     *
     * @return array{
     *     moveNumber: int,
     *     isAttackerTurn: bool,
     *     result: FightMoveResult,
     *     damage: int,
     *     attackerHealthAfter: int,
     *     defenderHealthAfter: int
     * }
     */
    private function resolveSingleHit(
        int $moveNumber,
        bool $isAttackerTurn,
        array $striker,
        array $target,
        int $attackerHp,
        int $defenderHp,
    ): array {
        $dodgeChance = $this->combatMath->dodgeChancePercentForDefender($striker['agility'], $target['agility']);
        if ($this->rollInt(1, 100) <= $dodgeChance) {
            return $this->afterMove(
                $moveNumber,
                $isAttackerTurn,
                FightMoveResult::DODGE,
                0,
                $attackerHp,
                $defenderHp,
            );
        }

        $critChance = $this->combatMath->criticalHitChancePercentForAttacker($striker['luck'], $target['luck']);
        $rawDamage = $this->combatMath->calculateDamage($striker['strength']);
        $result = FightMoveResult::HIT;

        if ($this->rollInt(1, 100) <= $critChance) {
            $rawDamage = (int) round($rawDamage * FightConstants::CRIT_DAMAGE_MULTIPLIER);
            $result = FightMoveResult::CRITICAL_HIT;
        }

        $damage = $this->combatMath->mitigateDamageByIntelligence(
            $rawDamage,
            $target['intelligence'],
            $striker['strength'],
        );

        if ($isAttackerTurn) {
            $defenderHp = max(0, $defenderHp - $damage);
        } else {
            $attackerHp = max(0, $attackerHp - $damage);
        }

        return $this->afterMove(
            $moveNumber,
            $isAttackerTurn,
            $result,
            $damage,
            $attackerHp,
            $defenderHp,
        );
    }

    /**
     * @return array{
     *     moveNumber: int,
     *     isAttackerTurn: bool,
     *     result: FightMoveResult,
     *     damage: int,
     *     attackerHealthAfter: int,
     *     defenderHealthAfter: int
     * }
     */
    private function afterMove(
        int $moveNumber,
        bool $isAttackerTurn,
        FightMoveResult $result,
        int $damage,
        int $attackerHp,
        int $defenderHp,
    ): array {
        return [
            'moveNumber' => $moveNumber,
            'isAttackerTurn' => $isAttackerTurn,
            'result' => $result,
            'damage' => $damage,
            'attackerHealthAfter' => $attackerHp,
            'defenderHealthAfter' => $defenderHp,
        ];
    }

    private function rollInt(int $min, int $max): int
    {
        if ($this->randomizer !== null) {
            return $this->randomizer->int($min, $max);
        }

        return random_int($min, $max);
    }
}
