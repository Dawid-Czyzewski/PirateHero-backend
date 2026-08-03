<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Combat;

use App\Entity\Ship;
use App\Entity\User;
use App\Repository\ShipRepository;
use App\Service\Combat\ShipsFightMatchmaker;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class ShipsFightMatchmakerTest extends TestCase
{
    public function testGetActiveMembersSkipsUnactivatedUsers(): void
    {
        $active = $this->createConfiguredMock(User::class, ['getActivateToken' => null]);
        $pending = $this->createConfiguredMock(User::class, ['getActivateToken' => 'token']);

        $activeMember = new \App\Entity\ShipMember();
        $activeMember->setUser($active);
        $pendingMember = new \App\Entity\ShipMember();
        $pendingMember->setUser($pending);

        $ship = $this->createMock(Ship::class);
        $ship->method('getMembers')->willReturn(new ArrayCollection([$activeMember, $pendingMember]));

        $matchmaker = new ShipsFightMatchmaker(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ShipRepository::class),
        );

        self::assertSame([$active], $matchmaker->getActiveMembers($ship));
    }

    public function testCanStartFightTodayReturnsFalseWhenCountPositive(): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        foreach (['select', 'from', 'join', 'where', 'andWhere', 'setParameter'] as $method) {
            $qb->method($method)->willReturnSelf();
        }

        $query = $this->createMock(Query::class);
        $query->method('getSingleScalarResult')->willReturn(1);
        $qb->method('getQuery')->willReturn($query);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('createQueryBuilder')->willReturn($qb);

        $owner = $this->createConfiguredMock(User::class, ['getId' => '5']);

        $matchmaker = new ShipsFightMatchmaker($em, $this->createMock(ShipRepository::class));

        self::assertFalse($matchmaker->canStartFightToday($owner));
    }
}
