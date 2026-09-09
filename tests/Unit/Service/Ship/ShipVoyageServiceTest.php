<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Ship;

use App\Domain\Constants\ShipVoyageConstants;
use App\Entity\Level;
use App\Entity\Ship;
use App\Entity\ShipMember;
use App\Entity\User;
use App\Enum\ShipRole;
use App\Exception\BusinessRuleException;
use App\Repository\ShipMemberRepository;
use App\Repository\ShipVoyageRepository;
use App\Service\Progression\TimedActivity\TimedActivityLifecycle;
use App\Service\Ship\ShipMembershipService;
use App\Service\Ship\ShipVoyageService;
use App\Service\User\LevelService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ShipVoyageServiceTest extends TestCase
{
    public function testStartThrowsOnInvalidDuration(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $service = new ShipVoyageService(
            $em,
            $this->createMock(ShipVoyageRepository::class),
            $this->createMock(ShipMemberRepository::class),
            $this->createMock(ShipMembershipService::class),
            new TimedActivityLifecycle($em),
            $this->createMock(LevelService::class),
        );
        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('shipVoyageInvalidDuration');
        $service->start($this->makeUser(), 999);
    }

    public function testOffersIncludePlanDurations(): void
    {
        $user = $this->makeUser();
        $ship = (new Ship())->setTitle('S')->setGold(10_000);
        $ship->setMissionsUpgrade(0);
        $ship->setHullUpgrade(0);

        $member = (new ShipMember())->setUser($user)->setShip($ship)->setRole(ShipRole::OWNER);
        $memberRepo = $this->createMock(ShipMemberRepository::class);
        $memberRepo->method('findBy')->willReturn([$member]);

        $membership = $this->createMock(ShipMembershipService::class);
        $membership->method('getShipForUser')->willReturn($ship);
        $membership->method('isUserOwner')->willReturn(true);

        $voyageRepo = $this->createMock(ShipVoyageRepository::class);
        $voyageRepo->method('findActiveForShip')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $service = new ShipVoyageService(
            $em,
            $voyageRepo,
            $memberRepo,
            $membership,
            new TimedActivityLifecycle($em),
            $this->createMock(LevelService::class),
        );

        $status = $service->getStatus($user);
        self::assertCount(3, $status['offers']);
        self::assertSame(ShipVoyageConstants::OFFER_DURATIONS_SECONDS, array_column($status['offers'], 'durationSeconds'));
    }

    private function makeUser(): User
    {
        return (new User())
            ->setEmail('voyage@test.local')
            ->setUsername('voyager')
            ->setPassword('hash')
            ->setLevel((new Level())->setName('5')->setExpToNextLevel(100))
            ->setGold(100);
    }
}
