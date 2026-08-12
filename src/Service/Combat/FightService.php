<?php

declare(strict_types=1);

namespace App\Service\Combat;

use App\Domain\Constants\FightConstants;
use App\Entity\FightMove;
use App\Entity\FightScore;
use App\Entity\User;
use App\Entity\UsersFight;
use App\Enum\FightMoveResult;
use App\Enum\FightResult;
use App\Enum\QuestCategory;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Mapper\Api\FightMapper;
use App\Service\Progression\DailyChallengeService;
use App\Service\Progression\QuestProgressService;
use App\Service\Progression\QuestService;
use App\Service\ShopBoosters\CombatStatisticsProvider;
use App\Service\User\SimilarUsersResolver;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

readonly class FightService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SimilarUsersResolver $similarUsersResolver,
        private readonly QuestProgressService $questProgressService,
        private readonly QuestService $questService,
        private readonly TurnBasedDuelResolver $duelResolver,
        private readonly CombatStatisticsProvider $combatStatisticsProvider,
        private readonly DailyChallengeService $dailyChallengeService,
    ) {
    }

    public function getAvailableOpponents(User $user): array
    {
        $opponents = $this->similarUsersResolver->findSimilarByAverageSkill($user, FightConstants::SIMILAR_OPPONENTS_LIMIT);

        $opponentsData = [];
        foreach ($opponents as $opponent) {
            $opponentsData[] = [
                'id' => $opponent->getId(),
                'username' => $opponent->getUsername(),
                'avatarName' => $opponent->getAvatarName(),
                'famePoints' => $opponent->getFamePoints(),
                'level' => $opponent->getLevel()?->getName() ?? '1',
                'averageSkill' => $opponent->getAverageSkill(),
                'totalStats' => $this->calculateTotalStats($opponent),
            ];
        }

        shuffle($opponentsData);

        return $opponentsData;
    }

    public function startFight(User $attacker, User $defender): array
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $this->entityManager->lock($attacker, LockMode::PESSIMISTIC_WRITE);
            $this->entityManager->lock($defender, LockMode::PESSIMISTIC_WRITE);
            if ($attacker->getDuelPoints() < FightConstants::DUEL_COST_POINTS) {
                throw new BusinessRuleException('notEnoughDuelPoints');
            }

            $attackerStats = $this->calculateTotalStats($attacker);
            $defenderStats = $this->calculateTotalStats($defender);

            $attackerHealth = max(1, $attackerStats['health']) * FightConstants::DUEL_HP_MULTIPLIER;
            $defenderHealth = max(1, $defenderStats['health']) * FightConstants::DUEL_HP_MULTIPLIER;

            $resolvedMoves = $this->duelResolver->resolve(
                $attackerHealth,
                $defenderHealth,
                $attackerStats,
                $defenderStats,
            );

            $fight = new UsersFight();
            $fight->setAttacker($attacker);
            $fight->setDefender($defender);
            $fight->setCreatedAt(new \DateTimeImmutable());
            $fight->setAttackerMaxHp($attackerHealth);
            $fight->setDefenderMaxHp($defenderHealth);

            $fightMoves = [];
            foreach ($resolvedMoves as $row) {
                $fightingUser = $row['isAttackerTurn'] ? $attacker : $defender;
                $move = new FightMove();
                $move->setFight($fight);
                $move->setPlayer($fightingUser);
                $move->setMoveNumber($row['moveNumber']);
                $move->setResult($row['result']);
                $move->setDamage($row['damage']);
                $move->setAttackerHealthAfter($row['attackerHealthAfter']);
                $move->setDefenderHealthAfter($row['defenderHealthAfter']);
                $fightMoves[] = $move;
            }

            if ($resolvedMoves === []) {
                throw new \RuntimeException('Duel produced no moves.');
            }
            $last = $resolvedMoves[array_key_last($resolvedMoves)];
            if ($last['attackerHealthAfter'] > 0 && $last['defenderHealthAfter'] > 0) {
                $isAttackerVictory = $last['attackerHealthAfter'] > $last['defenderHealthAfter'];
            } else {
                $isAttackerVictory = $last['defenderHealthAfter'] <= 0;
            }
            $result = $isAttackerVictory ? FightResult::ATTACKER_WON : FightResult::DEFENDER_WON;
            $fight->setResult($result);

            $attackerFamePoints = $isAttackerVictory ? FightConstants::PVP_WIN_FAME : -FightConstants::PVP_LOSE_FAME;
            $defenderFamePoints = $isAttackerVictory ? -FightConstants::PVP_LOSE_FAME : FightConstants::PVP_WIN_FAME;
            $fight->setScore(new FightScore($attackerFamePoints, $defenderFamePoints));

            $this->entityManager->persist($fight);
            foreach ($fightMoves as $move) {
                $this->entityManager->persist($move);
            }

            $attacker->spendDuelPoints(FightConstants::DUEL_COST_POINTS);
            $attacker->addFamePoints($attackerFamePoints);
            $defender->addFamePoints($defenderFamePoints);
            $this->entityManager->flush();
            $connection->commit();

            if ($isAttackerVictory) {
                $this->questProgressService->checkAndUpdateProgress($attacker, QuestCategory::FIGHTS_WON, 1);
                $this->questProgressService->checkAndUpdateProgress($defender, QuestCategory::FIGHTS_LOST, 1);
                $this->dailyChallengeService->recordArenaWins($attacker, 1);
            } else {
                $this->questProgressService->checkAndUpdateProgress($defender, QuestCategory::FIGHTS_WON, 1);
                $this->questProgressService->checkAndUpdateProgress($attacker, QuestCategory::FIGHTS_LOST, 1);
                $this->dailyChallengeService->recordArenaWins($defender, 1);
            }

            $opponentsNext = $this->getAvailableOpponents($attacker);

            return FightMapper::startResponse([
                'fightId' => $fight->getId(),
                'result' => $isAttackerVictory ? 'victory' : 'defeat',
                'attackerScore' => $attackerFamePoints,
                'defenderScore' => $defenderFamePoints,
                'famePointsChange' => $attackerFamePoints,
                'duelPointsSpent' => FightConstants::DUEL_COST_POINTS,
                'playerId' => $attacker->getId(),
                'opponentId' => $defender->getId(),
                'attackerUsername' => $attacker->getUsername(),
                'moves' => array_map([$this, 'formatMove'], $fightMoves),
                'attackerStats' => $attackerStats,
                'defenderStats' => $defenderStats,
                'opponent' => [
                    'id' => $defender->getId(),
                    'username' => $defender->getUsername(),
                    'avatarName' => $defender->getAvatarName(),
                    'famePoints' => $defender->getFamePoints(),
                ],
                'opponents' => $opponentsNext,
            ])->toArray();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * Runs a duel, then refreshes/initializes quests and merges quest fields into the fight payload.
     *
     * @return array<string, mixed>
     */
    public function startFightWithQuestPayload(User $attacker, User $defender): array
    {
        $result = $this->startFight($attacker, $defender);

        $this->entityManager->refresh($attacker);
        $this->questProgressService->initializeUserQuests($attacker);
        $this->entityManager->flush();
        $this->entityManager->refresh($attacker);

        return $this->questService->mergeQuestPayload($result, $attacker);
    }

    /**
     * @return array<string, mixed>
     */
    public function startFightWithQuestPayloadByOpponentId(User $attacker, string $opponentId): array
    {
        $opponent = $this->entityManager->find(User::class, $opponentId);
        if (!$opponent instanceof User) {
            throw new ResourceNotFoundException('opponentNotFound');
        }

        return $this->startFightWithQuestPayload($attacker, $opponent);
    }

    private function formatMove(FightMove $move): array
    {
        return FightMapper::moveFromArray([
            'moveNumber' => $move->getMoveNumber(),
            'player' => [
                'id' => $move->getPlayer()->getId(),
                'username' => $move->getPlayer()->getUsername(),
            ],
            'result' => $move->getResult()->value,
            'damage' => $move->getDamage(),
            'attackerHealthAfter' => $move->getAttackerHealthAfter(),
            'defenderHealthAfter' => $move->getDefenderHealthAfter(),
        ])->toArray();
    }

    /** @return array<string, int> */
    private function calculateTotalStats(User $user): array
    {
        return $this->combatStatisticsProvider->getCombatStatistics($user);
    }

    public function getFightHistory(User $user): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('f')
            ->from('App\Entity\UsersFight', 'f')
            ->where('f.attacker = :user OR f.defender = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->setMaxResults(20);

        $fights = $qb->getQuery()->getResult();

        $history = [];
        foreach ($fights as $fight) {
            $isAttacker = $fight->getAttacker()->getId() === $user->getId();
            $opponent = $isAttacker ? $fight->getDefender() : $fight->getAttacker();

            $userResult = 'defeat';
            if ($fight->getResult() === FightResult::ATTACKER_WON && $isAttacker) {
                $userResult = 'victory';
            } elseif ($fight->getResult() === FightResult::DEFENDER_WON && !$isAttacker) {
                $userResult = 'victory';
            }

            $fameChange = 0;
            if ($userResult === 'victory') {
                $fameChange = FightConstants::PVP_WIN_FAME;
            } else {
                $fameChange = -FightConstants::PVP_LOSE_FAME;
            }

            $history[] = FightMapper::historyItemFromArray([
                'id' => $fight->getId(),
                'opponent' => [
                    'id' => $opponent->getId(),
                    'username' => $opponent->getUsername(),
                ],
                'result' => $userResult,
                'famePointsChange' => $fameChange,
                'date' => $fight->getCreatedAt()->format('Y-m-d H:i'),
                'wasAttacker' => $isAttacker,
            ])->toArray();
        }

        return $history;
    }

    /**
     * Full duel replay for a participant (moves + nicks + max HP). Non-participants get 404.
     *
     * @return array{
     *     fightId: int,
     *     viewerWasAttacker: bool,
     *     resultForViewer: 'victory'|'defeat',
     *     famePointsChangeForViewer: int,
     *     attacker: array{id: int, username: string, avatarName: string|null},
     *     defender: array{id: int, username: string, avatarName: string|null},
     *     attackerMaxHp: int,
     *     defenderMaxHp: int,
     *     moves: list<array<string, mixed>>
     * }
     */
    public function getFightReplayForUser(User $viewer, int $fightId): array
    {
        $fight = $this->entityManager->find(UsersFight::class, $fightId);
        if (!$fight instanceof UsersFight) {
            throw new ResourceNotFoundException('fightNotFound');
        }

        $viewerId = $viewer->getId();
        $isAttacker = $fight->getAttacker()->getId() === $viewerId;
        $isDefender = $fight->getDefender()->getId() === $viewerId;
        if (!$isAttacker && !$isDefender) {
            throw new ResourceNotFoundException('fightNotFound');
        }

        $moves = $fight->getFightMoves()->toArray();
        usort($moves, static fn (FightMove $a, FightMove $b): int => $a->getMoveNumber() <=> $b->getMoveNumber());

        $attackerMaxHp = $fight->getAttackerMaxHp();
        $defenderMaxHp = $fight->getDefenderMaxHp();
        if (null === $attackerMaxHp || null === $defenderMaxHp) {
            [$inferredA, $inferredD] = $this->inferInitialPoolsFromMoves($fight, $moves);
            $attackerMaxHp ??= $inferredA ?? 1;
            $defenderMaxHp ??= $inferredD ?? 1;
        }

        $userResult = 'defeat';
        if ($fight->getResult() === FightResult::ATTACKER_WON && $isAttacker) {
            $userResult = 'victory';
        } elseif ($fight->getResult() === FightResult::DEFENDER_WON && !$isAttacker) {
            $userResult = 'victory';
        }

        $fameChangeForViewer = 'victory' === $userResult ? FightConstants::PVP_WIN_FAME : -FightConstants::PVP_LOSE_FAME;

        $attacker = $fight->getAttacker();
        $defender = $fight->getDefender();

        return FightMapper::replayResponse([
            'fightId' => $fight->getId(),
            'viewerWasAttacker' => $isAttacker,
            'resultForViewer' => $userResult,
            'famePointsChangeForViewer' => $fameChangeForViewer,
            'attacker' => [
                'id' => $attacker->getId(),
                'username' => $attacker->getUsername(),
                'avatarName' => $attacker->getAvatarName(),
            ],
            'defender' => [
                'id' => $defender->getId(),
                'username' => $defender->getUsername(),
                'avatarName' => $defender->getAvatarName(),
            ],
            'attackerMaxHp' => $attackerMaxHp,
            'defenderMaxHp' => $defenderMaxHp,
            'moves' => array_map([$this, 'formatMove'], $moves),
        ])->toArray();
    }

    /**
     * @param list<FightMove> $sortedMoves
     *
     * @return array{0: ?int, 1: ?int}
     */
    private function inferInitialPoolsFromMoves(UsersFight $fight, array $sortedMoves): array
    {
        if ($sortedMoves === []) {
            return [null, null];
        }

        $first = $sortedMoves[0];
        $attackerId = $fight->getAttacker()->getId();
        $isAttackerStriker = $first->getPlayer()->getId() === $attackerId;
        $dodged = FightMoveResult::DODGE === $first->getResult();
        $dmg = $first->getDamage();
        $a1 = $first->getAttackerHealthAfter();
        $d1 = $first->getDefenderHealthAfter();

        if ($isAttackerStriker) {
            $a0 = $a1;
            $d0 = $dodged ? $d1 : $d1 + $dmg;
        } else {
            $d0 = $d1;
            $a0 = $dodged ? $a1 : $a1 + $dmg;
        }

        return [$a0, $d0];
    }
}
