<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Entity\Level;
use App\Entity\Ship;
use App\Entity\User;
use App\Entity\UserActualActivity;
use App\Entity\Work;
use App\Exception\BusinessRuleException;
use App\Service\Progression\TimedActivity\OwnedTimedActivityResolver;
use App\Service\Progression\TimedActivity\TimedActivityLifecycle;
use App\Service\Progression\UserWriteLockExecutor;
use App\Service\Progression\WorkRewardCalculator;
use App\Service\Progression\WorkService;
use App\Service\Ship\ShipMembershipService;
use App\Service\ShopBoosters\ShopBoosterSessionService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class WorkServiceTest extends TestCase
{
    public function testGenerateWorksForUserCreatesExactlyFiveWorkOffers(): void
    {
        $persistedWorks = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(static function (object $entity) use (&$persistedWorks): void {
            if ($entity instanceof Work) {
                $persistedWorks[] = $entity;
            }
        });
        $em->expects($this->once())->method('flush');

        $user = $this->makeUser();
        $service = $this->makeService($em);
        $service->generateWorksForUser($user);

        self::assertCount(WorkService::WORK_OFFER_COUNT, $persistedWorks);
        self::assertCount(WorkService::WORK_OFFER_COUNT, $user->getWorks());
        self::assertSame('work.kitchen_helper', $persistedWorks[0]->getTitle());
        self::assertSame('work.tavern_server', $persistedWorks[4]->getTitle());
        self::assertSame(6, $persistedWorks[4]->getHoursCount());
    }

    public function testStartWorkThrowsWhenActivityAlreadyInProgress(): void
    {
        $user = $this->makeUser();
        $user->setCurrentActivity(new UserActualActivity());

        $service = $this->makeService($this->mockTransactionalEm($user));

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('activityAlreadyInProgress');
        $service->startWork($user, $this->makeWork($user));
    }

    public function testCompleteWorkThrowsWhenNoActivity(): void
    {
        $user = $this->makeUser();
        $service = $this->makeService($this->mockTransactionalEm($user));

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('noActiveActivity');
        $service->completeWork($user);
    }

    public function testFormatWorkForUserAppliesMinimumPlusOneWhenWorkUpgradeRoundsToSameBase(): void
    {
        $ship = $this->createMock(Ship::class);
        $ship->method('getWorkUpgrade')->willReturn(1);

        $shipMembership = $this->createMock(ShipMembershipService::class);
        $shipMembership->method('getShipForUser')->willReturn($ship);

        $em = $this->createMock(EntityManagerInterface::class);
        $service = new WorkService(
            $em,
            $shipMembership,
            $this->workRewardCalculator(),
            new UserWriteLockExecutor($em),
            new TimedActivityLifecycle($em),
            new OwnedTimedActivityResolver($em),
        );

        $user = $this->makeUser();
        $work = $this->makeWork($user);
        $work->setBaseGold(12);
        $work->setHoursCount(2);

        $formatted = $service->formatWorkForUser($user, $work);

        self::assertSame(1, $formatted['bonusPercent']);
        self::assertSame(24, 12 * 2 * 1);
        self::assertSame(25, $formatted['totalGoldAfterShip']);
    }

    private function makeService(EntityManagerInterface $em): WorkService
    {
        return new WorkService(
            $em,
            $this->createMock(ShipMembershipService::class),
            $this->workRewardCalculator(),
            new UserWriteLockExecutor($em),
            new TimedActivityLifecycle($em),
            new OwnedTimedActivityResolver($em),
        );
    }

    private function mockTransactionalEm(User $user): EntityManagerInterface
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction');
        $connection->method('commit');
        $connection->method('rollBack');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('find')->willReturnCallback(
            static function (string $class, mixed $id, ?int $lockMode = null) use ($user) {
                if ($class === User::class && $lockMode === LockMode::PESSIMISTIC_WRITE) {
                    return $user;
                }

                return null;
            }
        );

        return $em;
    }

    private function workRewardCalculator(): WorkRewardCalculator
    {
        return new WorkRewardCalculator($this->shopBoosterPassthrough());
    }

    private function shopBoosterPassthrough(): ShopBoosterSessionService
    {
        $m = $this->createMock(ShopBoosterSessionService::class);
        $m->method('pruneExpiredSessions');
        $m->method('getWorkShopBoosterFraction')->willReturn(0.0);

        return $m;
    }

    private function makeUser(): User
    {
        $level = (new Level())->setName('1')->setExpToNextLevel(220);

        return (new User())
            ->setEmail(sprintf('work_%s@test.local', bin2hex(random_bytes(4))))
            ->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))))
            ->setPassword('hash')
            ->setLevel($level)
            ->setGold(100)
            ->setdiamonds(10)
            ->setEnergyPoints(100)
            ->setTrainingPoints(10)
            ->setDuelPoints(10)
            ->setFamePoints(10);
    }

    private function makeWork(User $user): Work
    {
        return (new Work())
            ->setTitle('w')
            ->setBaseGold(10)
            ->setHoursCount(1)
            ->setUser($user);
    }
}
