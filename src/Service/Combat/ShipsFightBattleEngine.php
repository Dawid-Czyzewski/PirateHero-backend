<?php

declare(strict_types=1);

namespace App\Service\Combat;

use App\Domain\Constants\FightConstants;
use App\Entity\ShipsFight;
use App\Entity\ShipsFightMember;
use App\Entity\ShipsFightMove;
use App\Entity\User;
use App\Enum\FightMoveResult;
use App\Service\Random\RandomizerInterface;
use App\Service\ShopBoosters\ShopBoosterSessionService;

class ShipsFightBattleEngine
{
    private const MAX_MOVES = 1000;

    public function __construct(
        private readonly CombatMathService $combatMathService,
        private readonly ShopBoosterSessionService $shopBoosterSessionService,
        private readonly ?RandomizerInterface $randomizer = null,
    ) {
    }

    /**
     * @param list<User> $attackerMembers
     * @param list<User> $defenderMembers
     *
     * @return array{
     *     attackerFightMembers: list<array{member: ShipsFightMember, user: User, stats: array<string, int>, health: int}>,
     *     defenderFightMembers: list<array{member: ShipsFightMember, user: User, stats: array<string, int>, health: int}>,
     *     moves: list<ShipsFightMove>,
     *     attackerWon: bool,
     * }
     */
    public function simulate(ShipsFight $fight, array $attackerMembers, array $defenderMembers): array
    {
        $attackerFightMembers = $this->buildRoster($fight, $attackerMembers, true);
        $defenderFightMembers = $this->buildRoster($fight, $defenderMembers, false);

        $moves = [];
        $moveNumber = 1;
        $attackerIndex = 0;
        $defenderIndex = 0;

        while (true) {
            $attackerIndex = $this->findNextActive($attackerFightMembers, $attackerIndex);
            $defenderIndex = $this->findNextActive($defenderFightMembers, $defenderIndex);

            if ($attackerIndex === -1 || $defenderIndex === -1) {
                break;
            }

            $attackerData = $attackerFightMembers[$attackerIndex];
            $defenderData = $defenderFightMembers[$defenderIndex];

            $attackerUser = $attackerData['user'];
            $defenderUser = $defenderData['user'];
            $attackerStats = $attackerData['stats'];
            $defenderStats = $defenderData['stats'];
            $defenderCurrentHealth = $defenderData['member']->getCurrentHealth();

            $move = $this->executeMove(
                $fight,
                $attackerUser,
                $defenderUser,
                $moveNumber,
                $attackerStats,
                $defenderStats,
                $defenderCurrentHealth
            );
            $moves[] = $move;

            $newDefenderHealth = $move->getTargetHealthAfter();
            $defenderData['member']->setCurrentHealth($newDefenderHealth);

            if ($newDefenderHealth <= 0) {
                $defenderData['member']->setIsDefeated(true);
            }

            $defenderIndex = $this->findNextActive($defenderFightMembers, $defenderIndex);
            if ($defenderIndex === -1) {
                break;
            }

            $defenderData = $defenderFightMembers[$defenderIndex];
            $attackerCurrentHealth = $attackerData['member']->getCurrentHealth();

            $move = $this->executeMove(
                $fight,
                $defenderData['user'],
                $attackerUser,
                $moveNumber + 1,
                $defenderData['stats'],
                $attackerStats,
                $attackerCurrentHealth
            );
            $moves[] = $move;

            $newAttackerHealth = $move->getTargetHealthAfter();
            $attackerData['member']->setCurrentHealth($newAttackerHealth);

            if ($newAttackerHealth <= 0) {
                $attackerData['member']->setIsDefeated(true);
            }

            $moveNumber += 2;

            if ($moveNumber > self::MAX_MOVES) {
                break;
            }
        }

        $attackerWon = $this->hasActiveMembers($attackerFightMembers) && !$this->hasActiveMembers($defenderFightMembers);

        return [
            'attackerFightMembers' => $attackerFightMembers,
            'defenderFightMembers' => $defenderFightMembers,
            'moves' => $moves,
            'attackerWon' => $attackerWon,
        ];
    }

    /**
     * @param list<User> $members
     *
     * @return list<array{member: ShipsFightMember, user: User, stats: array<string, int>, health: int}>
     */
    private function buildRoster(ShipsFight $fight, array $members, bool $isAttackerSide): array
    {
        $roster = [];
        foreach ($members as $member) {
            $stats = $this->shopBoosterSessionService->getCombatStatistics($member);
            $health = $stats['health'] * 2;

            $fightMember = new ShipsFightMember();
            $fightMember->setUser($member);
            $fightMember->setIsAttackerSide($isAttackerSide);
            $fightMember->setInitialHealth($health);
            $fightMember->setCurrentHealth($health);
            $fightMember->setIsDefeated(false);
            $fightMember->setShipsFight($fight);

            $roster[] = [
                'member' => $fightMember,
                'user' => $member,
                'stats' => $stats,
                'health' => $health,
            ];

            $fight->addFightMember($fightMember);
        }

        return $roster;
    }

    /**
     * @param list<array{member: ShipsFightMember, user: User, stats: array<string, int>, health: int}> $members
     */
    private function findNextActive(array $members, int $startIndex): int
    {
        for ($i = $startIndex; $i < count($members); ++$i) {
            if (!$members[$i]['member']->isDefeated()) {
                return $i;
            }
        }

        return -1;
    }

    /**
     * @param list<array{member: ShipsFightMember, user: User, stats: array<string, int>, health: int}> $members
     */
    private function hasActiveMembers(array $members): bool
    {
        foreach ($members as $data) {
            if (!$data['member']->isDefeated()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, int> $attackingStats
     * @param array<string, int> $defendingStats
     */
    private function executeMove(
        ShipsFight $fight,
        User $attackingUser,
        User $defendingUser,
        int $moveNumber,
        array $attackingStats,
        array $defendingStats,
        int $defenderCurrentHealth,
    ): ShipsFightMove {
        $move = new ShipsFightMove();
        $fight->addFightMove($move);
        $move->setPlayer($attackingUser);
        $move->setTarget($defendingUser);
        $move->setMoveNumber($moveNumber);

        $atk = CombatStatsNormalizer::forCombat($attackingStats);
        $def = CombatStatsNormalizer::forCombat($defendingStats);

        $dodgeChance = $this->combatMathService->dodgeChancePercentForDefender($atk['agility'], $def['agility']);
        $dodgeRoll = $this->randomInt(1, 100);

        if ($dodgeRoll <= $dodgeChance) {
            $move->setResult(FightMoveResult::DODGE);
            $move->setDamage(0);
            $move->setTargetHealthAfter($defenderCurrentHealth);
        } else {
            $criticalChance = $this->combatMathService->criticalHitChancePercentForAttacker($atk['luck'], $def['luck']);
            $criticalRoll = $this->randomInt(1, 100);

            if ($criticalRoll <= $criticalChance) {
                $rawDamage = $this->combatMathService->calculateDamage($atk['strength']);
                $damage = $this->combatMathService->mitigateDamageByIntelligence(
                    (int) round($rawDamage * FightConstants::CRIT_DAMAGE_MULTIPLIER),
                    $def['intelligence'],
                    $atk['strength'],
                );
                $move->setResult(FightMoveResult::CRITICAL_HIT);
            } else {
                $rawDamage = $this->combatMathService->calculateDamage($atk['strength']);
                $damage = $this->combatMathService->mitigateDamageByIntelligence(
                    $rawDamage,
                    $def['intelligence'],
                    $atk['strength'],
                );
                $move->setResult(FightMoveResult::HIT);
            }

            $move->setDamage($damage);
            $newDefenderHealth = max(0, $defenderCurrentHealth - $damage);
            $move->setTargetHealthAfter($newDefenderHealth);
        }

        return $move;
    }

    private function randomInt(int $min, int $max): int
    {
        if ($this->randomizer !== null) {
            return $this->randomizer->int($min, $max);
        }

        return random_int($min, $max);
    }
}
