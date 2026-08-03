<?php

declare(strict_types=1);

namespace App\Service\Combat;

use App\Domain\Constants\FightConstants;
use App\Entity\FightScore;
use App\Entity\Ship;
use App\Entity\ShipsFight;
use App\Entity\User;
use App\Enum\FightResult;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class ShipsFightService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShipsFightMatchmaker $matchmaker,
        private ShipsFightSerializer $serializer,
        private ShipsFightRewardService $rewardService,
        private ShipsFightBattleEngine $battleEngine,
    ) {
    }

    public function getAvailableOpponents(Ship $ship): array
    {
        return $this->matchmaker->getAvailableOpponents($ship);
    }

    public function canStartFightToday(User $owner): bool
    {
        return $this->matchmaker->canStartFightToday($owner);
    }

    public function startFight(Ship $attackerShip, Ship $defenderShip, User $owner): array
    {
        if (
            $attackerShip->getId() !== null
            && $defenderShip->getId() !== null
            && $attackerShip->getId() === $defenderShip->getId()
        ) {
            throw new BusinessRuleException('cannotFightOwnShip');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $this->entityManager->lock($owner, LockMode::PESSIMISTIC_WRITE);
            $this->entityManager->lock($attackerShip, LockMode::PESSIMISTIC_WRITE);
            $this->entityManager->lock($defenderShip, LockMode::PESSIMISTIC_WRITE);

            if (!$this->matchmaker->canStartFightToday($owner)) {
                throw new BusinessRuleException('shipFightLimitReached');
            }

            $attackerMembers = $this->matchmaker->getActiveMembers($attackerShip);
            $defenderMembers = $this->matchmaker->getActiveMembers($defenderShip);

            if (empty($attackerMembers) || empty($defenderMembers)) {
                throw new BusinessRuleException('shipFightMembersRequired');
            }

            $fight = new ShipsFight();
            $fight->setAttackerShip($attackerShip);
            $fight->setDefenderShip($defenderShip);
            $fight->setCreatedAt(new \DateTimeImmutable());

            $battle = $this->battleEngine->simulate($fight, $attackerMembers, $defenderMembers);
            $attackerFightMembers = $battle['attackerFightMembers'];
            $defenderFightMembers = $battle['defenderFightMembers'];
            $attackerWon = $battle['attackerWon'];

            $result = $attackerWon ? FightResult::ATTACKER_WON : FightResult::DEFENDER_WON;
            $fight->setResult($result);
            $this->rewardService->createFightNotifications($attackerShip, $defenderShip, $attackerWon);

            $nominalAttackerDelta = $attackerWon ? FightConstants::SHIPS_WIN_FAME : -FightConstants::SHIPS_LOSE_FAME;
            $nominalDefenderDelta = $attackerWon ? -FightConstants::SHIPS_LOSE_FAME : FightConstants::SHIPS_WIN_FAME;

            $attackerFamePoints = $this->rewardService->effectiveFameDelta($attackerShip, $nominalAttackerDelta);
            $defenderFamePoints = $this->rewardService->effectiveFameDelta($defenderShip, $nominalDefenderDelta);

            $fight->setScore(new FightScore($attackerFamePoints, $defenderFamePoints));

            $this->entityManager->persist($fight);
            $this->entityManager->flush();

            foreach ($battle['moves'] as $move) {
                $this->entityManager->persist($move);
            }
            $this->entityManager->flush();

            foreach ($attackerFightMembers as $data) {
                $this->entityManager->persist($data['member']);
            }
            foreach ($defenderFightMembers as $data) {
                $this->entityManager->persist($data['member']);
            }
            $this->entityManager->flush();

            $this->rewardService->distributeFamePoints($attackerShip, $defenderShip, $attackerFamePoints, $defenderFamePoints);

            $attackerInitialHealth = 0;
            $defenderInitialHealth = 0;
            foreach ($attackerFightMembers as $data) {
                $attackerInitialHealth += $data['health'];
            }
            foreach ($defenderFightMembers as $data) {
                $defenderInitialHealth += $data['health'];
            }

            $connection->commit();

            return [
                'result' => $attackerWon ? 'victory' : 'defeat',
                'attackerScore' => $attackerFamePoints,
                'defenderScore' => $defenderFamePoints,
                'viewerFameChange' => $attackerFamePoints,
                'moves' => $this->serializer->serializeAggregatedMoves($fight),
                'attackerShip' => [
                    'id' => $attackerShip->getId(),
                    'title' => $attackerShip->getTitle(),
                ],
                'defenderShip' => [
                    'id' => $defenderShip->getId(),
                    'title' => $defenderShip->getTitle(),
                ],
                'attackerInitialHealth' => $attackerInitialHealth,
                'defenderInitialHealth' => $defenderInitialHealth,
                'attackerMembers' => array_map(static function (array $data): array {
                    return [
                        'id' => $data['user']->getId(),
                        'username' => $data['user']->getUsername(),
                        'avatarName' => $data['user']->getAvatarName(),
                        'initialHealth' => $data['health'],
                    ];
                }, $attackerFightMembers),
                'defenderMembers' => array_map(static function (array $data): array {
                    return [
                        'id' => $data['user']->getId(),
                        'username' => $data['user']->getUsername(),
                        'avatarName' => $data['user']->getAvatarName(),
                        'initialHealth' => $data['health'],
                    ];
                }, $defenderFightMembers),
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function getFightHistory(Ship $ship): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('cf')
           ->from('App\Entity\ShipsFight', 'cf')
           ->where('cf.attackerShip = :ship OR cf.defenderShip = :ship')
           ->setParameter('ship', $ship)
           ->orderBy('cf.createdAt', 'DESC')
           ->setMaxResults(20);

        $fights = $qb->getQuery()->getResult();

        return $this->serializer->buildHistoryItems($ship, $fights);
    }

    public function getFightDetails(int $fightId, Ship $ship): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('cf', 'fm', 'fmove', 'ac', 'dc', 'memUser', 'movePlayer', 'moveTarget')
           ->from('App\Entity\ShipsFight', 'cf')
           ->leftJoin('cf.fightMembers', 'fm')
           ->leftJoin('cf.fightMoves', 'fmove')
           ->leftJoin('cf.attackerShip', 'ac')
           ->leftJoin('cf.defenderShip', 'dc')
           ->leftJoin('fm.user', 'memUser')
           ->leftJoin('fmove.player', 'movePlayer')
           ->leftJoin('fmove.target', 'moveTarget')
           ->where('cf.id = :fightId')
           ->setParameter('fightId', $fightId);

        $fight = $qb->getQuery()->getOneOrNullResult();

        if (!$fight) {
            throw new ResourceNotFoundException('shipFightNotFound');
        }

        return $this->serializer->buildFightDetailsPayload($fight, $ship);
    }

    /**
     * @return array<string, mixed>
     */
    public function startFightByOpponentShipId(Ship $attackerShip, int $opponentShipId, User $owner): array
    {
        $defenderShip = $this->entityManager->find(Ship::class, $opponentShipId);
        if (!$defenderShip instanceof Ship) {
            throw new ResourceNotFoundException('opponentShipNotFound');
        }

        return $this->startFight($attackerShip, $defenderShip, $owner);
    }
}
