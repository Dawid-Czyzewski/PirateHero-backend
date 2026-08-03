<?php

declare(strict_types=1);

namespace App\Service\Combat;

use App\Entity\Ship;
use App\Entity\User;
use App\Repository\ShipRepository;
use Doctrine\ORM\EntityManagerInterface;

class ShipsFightMatchmaker
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShipRepository $shipRepository,
    ) {
    }

    public function getAvailableOpponents(Ship $ship): array
    {
        $shipFamePoints = $this->calculateShipFamePoints($ship);

        $qb = $this->shipRepository->createQueryBuilder('c')
            ->leftJoin('c.members', 'cm')
            ->leftJoin('cm.user', 'u')
            ->addSelect('cm', 'u')
            ->where('c.id != :shipId')
            ->setParameter('shipId', $ship->getId())
            ->getQuery();

        $allShips = $qb->getResult();

        $shipRankings = [];
        foreach ($allShips as $opponentShip) {
            $members = $opponentShip->getMembers();
            $totalFamePoints = $opponentShip->getFamePoints();
            $activeMemberCount = 0;

            foreach ($members as $member) {
                $user = $member->getUser();
                if ($user && $user->getActivateToken() === null) {
                    ++$activeMemberCount;
                }
            }

            if ($activeMemberCount > 0) {
                $shipRankings[] = [
                    'ship' => $opponentShip,
                    'totalFamePoints' => $totalFamePoints,
                    'memberCount' => $activeMemberCount,
                    'difference' => abs($totalFamePoints - $shipFamePoints),
                ];
            }
        }

        usort($shipRankings, static function ($a, $b) {
            return $a['difference'] <=> $b['difference'];
        });

        $opponents = array_slice($shipRankings, 0, 5);

        $opponentsData = [];
        foreach ($opponents as $opponent) {
            $opponentsData[] = [
                'id' => $opponent['ship']->getId(),
                'title' => $opponent['ship']->getTitle(),
                'totalFamePoints' => $opponent['totalFamePoints'],
                'memberCount' => $opponent['memberCount'],
            ];
        }

        return $opponentsData;
    }

    public function canStartFightToday(User $owner): bool
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('COUNT(cf.id)')
           ->from('App\Entity\ShipsFight', 'cf')
           ->join('cf.attackerShip', 'ac')
           ->join('ac.members', 'm')
           ->join('m.user', 'u')
           ->where('u.id = :ownerId')
           ->andWhere('cf.createdAt >= :today')
           ->andWhere('cf.createdAt < :tomorrow')
           ->setParameter('ownerId', $owner->getId())
           ->setParameter('today', $today)
           ->setParameter('tomorrow', $tomorrow);

        $count = $qb->getQuery()->getSingleScalarResult();

        return $count == 0;
    }

    public function getActiveMembers(Ship $ship): array
    {
        $members = [];
        foreach ($ship->getMembers() as $shipMember) {
            $user = $shipMember->getUser();
            if ($user && $user->getActivateToken() === null) {
                $members[] = $user;
            }
        }

        return $members;
    }

    public function calculateShipFamePoints(Ship $ship): int
    {
        return $ship->getFamePoints();
    }
}
