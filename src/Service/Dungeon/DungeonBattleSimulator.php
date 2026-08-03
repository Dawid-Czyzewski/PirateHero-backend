<?php

declare(strict_types=1);

namespace App\Service\Dungeon;

use App\Domain\Constants\DungeonConstants;
use App\Domain\Constants\FightConstants;
use App\Service\Combat\CombatMathService;
use App\Service\Random\RandomizerInterface;

class DungeonBattleSimulator
{
    public function __construct(
        private readonly CombatMathService $combatMath,
    ) {
    }

    /**
     * @param array{strength: int, agility: int, endurance: int, intelligence: int, luck: int} $player
     * @param array{strength: int, agility: int, endurance: int, intelligence: int, luck: int} $opponent
     *
     * @return array{
     *     won: bool,
     *     logs: list<array{attackerIsPlayer: bool, damage: int, critical: bool}>,
     *     playerMaxHp: int,
     *     opponentMaxHp: int,
     *     fameEarned: int,
     *     famePointsChange: int
     * }
     */
    public function simulate(array $player, array $opponent, RandomizerInterface $rng): array
    {
        $playerMaxHp = max(1, (int) $player['endurance']) * DungeonConstants::HP_POOL_MULTIPLIER;
        $opponentMaxHp = max(1, (int) $opponent['endurance']) * DungeonConstants::HP_POOL_MULTIPLIER;
        $playerHp = $playerMaxHp;
        $opponentHp = $opponentMaxHp;
        $logs = [];

        $playerStarts = $this->resolvePlayerStarts((int) $player['agility'], (int) $opponent['agility'], $rng);

        for ($move = 1; $move <= DungeonConstants::MAX_ROUNDS * 2 && $playerHp > 0 && $opponentHp > 0; ++$move) {
            $isPlayer = ($move % 2 === 1) ? $playerStarts : !$playerStarts;
            $attacker = $isPlayer ? $player : $opponent;
            $defender = $isPlayer ? $opponent : $player;

            $dodgeChance = $this->combatMath->dodgeChancePercentForDefender(
                (int) $attacker['agility'],
                (int) $defender['agility'],
            );
            if ($rng->int(1, 100) <= $dodgeChance) {
                $logs[] = ['attackerIsPlayer' => $isPlayer, 'damage' => 0, 'critical' => false];
                continue;
            }

            $raw = $this->combatMath->calculateDamageWithRandomizer((int) $attacker['strength'], $rng);
            $isCrit = $rng->int(1, 100) <= $this->combatMath->criticalHitChancePercentForAttacker(
                (int) $attacker['luck'],
                (int) $defender['luck'],
            );
            $dmg = $this->combatMath->mitigateDamageByIntelligence(
                $isCrit ? (int) floor($raw * FightConstants::CRIT_DAMAGE_MULTIPLIER) : $raw,
                (int) $defender['intelligence'],
                (int) $attacker['strength'],
            );

            if ($isPlayer) {
                $opponentHp = max(0, $opponentHp - $dmg);
            } else {
                $playerHp = max(0, $playerHp - $dmg);
            }

            $logs[] = ['attackerIsPlayer' => $isPlayer, 'damage' => $dmg, 'critical' => $isCrit];
        }

        return [
            'won' => $playerHp > $opponentHp,
            'logs' => $logs,
            'playerMaxHp' => $playerMaxHp,
            'opponentMaxHp' => $opponentMaxHp,
            'fameEarned' => 0,
            'famePointsChange' => 0,
        ];
    }

    private function resolvePlayerStarts(int $playerAgility, int $opponentAgility, RandomizerInterface $rng): bool
    {
        if ($playerAgility > $opponentAgility) {
            return true;
        }
        if ($playerAgility < $opponentAgility) {
            return false;
        }

        return $rng->int(0, 1) === 1;
    }
}
