<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Combat;

use App\Entity\Ship;
use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Repository\ShipRepository;
use App\Service\Combat\CombatMathService;
use App\Service\Combat\ShipsFightBattleEngine;
use App\Service\Combat\ShipsFightMatchmaker;
use App\Service\Combat\ShipsFightRewardService;
use App\Service\Combat\ShipsFightSerializer;
use App\Service\Combat\ShipsFightService;
use App\Service\ShopBoosters\ShopBoosterSessionService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class ShipsFightServiceTest extends TestCase
{
    public function testStartFightThrowsWhenDailyLimitReached(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('rollBack');

        $qb = $this->makeQueryBuilderMock();
        $query = $this->createMock(Query::class);
        $query->method('getSingleScalarResult')->willReturn(1);
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->expects(self::exactly(3))->method('lock')->with(self::anything(), LockMode::PESSIMISTIC_WRITE);
        $em->method('createQueryBuilder')->willReturn($qb);

        $owner = $this->createConfiguredMock(User::class, ['getId' => '1']);

        $service = $this->makeService($em);

        $attacker = $this->createMock(Ship::class);
        $defender = $this->createMock(Ship::class);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('shipFightLimitReached');
        $service->startFight($attacker, $defender, $owner);
    }

    public function testStartFightThrowsWhenNoActiveMembers(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('rollBack');

        $qb = $this->makeQueryBuilderMock();
        $query = $this->createMock(Query::class);
        $query->method('getSingleScalarResult')->willReturn(0);
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->expects(self::exactly(3))->method('lock')->with(self::anything(), LockMode::PESSIMISTIC_WRITE);
        $em->method('createQueryBuilder')->willReturn($qb);

        $owner = $this->createConfiguredMock(User::class, ['getId' => '1']);

        $attacker = $this->createMock(Ship::class);
        $attacker->method('getMembers')->willReturn(new ArrayCollection());
        $defender = $this->createMock(Ship::class);
        $defender->method('getMembers')->willReturn(new ArrayCollection());

        $service = $this->makeService($em);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('shipFightMembersRequired');
        $service->startFight($attacker, $defender, $owner);
    }

    public function testGetFightDetailsThrowsWhenFightMissing(): void
    {
        $qb = $this->makeQueryBuilderMock(['leftJoin']);
        $query = $this->createMock(Query::class);
        $query->method('getOneOrNullResult')->willReturn(null);
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $ship = $this->createConfiguredMock(Ship::class, ['getId' => 9]);

        $service = $this->makeService($em);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('shipFightNotFound');
        $service->getFightDetails(123, $ship);
    }

    public function testGetFightDetailsThrowsWhenShipNotParticipant(): void
    {
        $fight = $this->createMock(\App\Entity\ShipsFight::class);
        $attackerShip = $this->createConfiguredMock(Ship::class, ['getId' => 1]);
        $defenderShip = $this->createConfiguredMock(Ship::class, ['getId' => 2]);
        $fight->method('getAttackerShip')->willReturn($attackerShip);
        $fight->method('getDefenderShip')->willReturn($defenderShip);
        $fight->method('getFightMembers')->willReturn(new ArrayCollection());
        $fight->method('getFightMoves')->willReturn(new ArrayCollection());

        $qb = $this->makeQueryBuilderMock(['leftJoin']);
        $query = $this->createMock(Query::class);
        $query->method('getOneOrNullResult')->willReturn($fight);
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $strangerShip = $this->createConfiguredMock(Ship::class, ['getId' => 99]);

        $service = $this->makeService($em);

        $this->expectException(OperationForbiddenException::class);
        $this->expectExceptionMessage('shipFightNotParticipant');
        $service->getFightDetails(7, $strangerShip);
    }

    private function makeService(EntityManagerInterface $em): ShipsFightService
    {
        $matchmaker = new ShipsFightMatchmaker($em, $this->createMock(ShipRepository::class));
        $serializer = new ShipsFightSerializer();
        $rewardService = new ShipsFightRewardService($em, $matchmaker);
        $battleEngine = new ShipsFightBattleEngine(
            $this->createMock(CombatMathService::class),
            $this->createMock(ShopBoosterSessionService::class),
        );

        return new ShipsFightService(
            $em,
            $matchmaker,
            $serializer,
            $rewardService,
            $battleEngine,
        );
    }

    /**
     * @param list<string> $extraMethods
     */
    private function makeQueryBuilderMock(array $extraMethods = []): QueryBuilder
    {
        $qb = $this->createMock(QueryBuilder::class);
        foreach (array_merge(['select', 'from', 'join', 'where', 'andWhere', 'setParameter'], $extraMethods) as $method) {
            $qb->method($method)->willReturnSelf();
        }

        return $qb;
    }
}
