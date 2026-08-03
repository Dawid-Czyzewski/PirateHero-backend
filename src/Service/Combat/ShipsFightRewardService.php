<?php

declare(strict_types=1);

namespace App\Service\Combat;

use App\Domain\Constants\FightConstants;
use App\Entity\Ship;
use App\Entity\ShipFightNotification;
use Doctrine\ORM\EntityManagerInterface;

class ShipsFightRewardService
{
    public const WIN_REWARD_FAME_POINTS = FightConstants::SHIPS_WIN_FAME;
    public const LOSE_PENALTY_FAME_POINTS = FightConstants::SHIPS_LOSE_FAME;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShipsFightMatchmaker $matchmaker,
    ) {
    }

    public function createFightNotifications(Ship $attackerShip, Ship $defenderShip, bool $attackerWon): void
    {
        $attackerMembers = $this->matchmaker->getActiveMembers($attackerShip);
        $defenderMembers = $this->matchmaker->getActiveMembers($defenderShip);

        $attackerFightType = $attackerWon ? 'attacking_win' : 'attacking_loss';
        $defenderFightType = $attackerWon ? 'attacked_loss' : 'attacked_win';

        foreach ($attackerMembers as $member) {
            $notification = new ShipFightNotification();
            $notification->setUser($member);
            $notification->setShip($attackerShip);
            $notification->setAttackerShip($attackerShip);
            $notification->setDefenderShip($defenderShip);
            $notification->setFightType($attackerFightType);
            $this->entityManager->persist($notification);
        }

        foreach ($defenderMembers as $member) {
            $notification = new ShipFightNotification();
            $notification->setUser($member);
            $notification->setShip($defenderShip);
            $notification->setAttackerShip($attackerShip);
            $notification->setDefenderShip($defenderShip);
            $notification->setFightType($defenderFightType);
            $this->entityManager->persist($notification);
        }

        $this->entityManager->flush();
    }

    public function effectiveFameDelta(Ship $ship, int $nominalDelta): int
    {
        if ($nominalDelta >= 0) {
            return $nominalDelta;
        }

        $loss = min(abs($nominalDelta), $ship->getFamePoints());

        return -$loss;
    }

    public function distributeFamePoints(Ship $attackerShip, Ship $defenderShip, int $attackerFamePoints, int $defenderFamePoints): void
    {
        $attackerShip->addFamePoints($attackerFamePoints);
        $defenderShip->addFamePoints($defenderFamePoints);

        $this->entityManager->flush();
    }
}
