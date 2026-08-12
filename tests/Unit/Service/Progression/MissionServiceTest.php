<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Entity\Level;
use App\Entity\Mission;
use App\Entity\Ship;
use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Service\Progression\DailyChallengeService;
use App\Service\Progression\MissionEconomyRoller;
use App\Service\Progression\MissionRewardCalculator;
use App\Service\Progression\MissionService;
use App\Service\Progression\TimedActivity\OwnedTimedActivityResolver;
use App\Service\Progression\TimedActivity\TimedActivityLifecycle;
use App\Service\Progression\UserWriteLockExecutor;
use App\Service\Ship\ShipMembershipService;
use App\Service\ShopBoosters\ShopBoosterSessionService;
use App\Service\User\LevelService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class MissionServiceTest extends TestCase
{
    public function testTitlePoolHasAtLeastFortyEntries(): void
    {
        self::assertGreaterThanOrEqual(40, \count(MissionService::titlePool()));
    }

    public function testStartMissionThrowsWhenMissionMissing(): void
    {
        $user = $this->makeUser();
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn(null);
        $this->mockTransactionalEm($em, $user, $repo);

        $service = $this->makeService($em);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('missionNotFound');
        $service->startMission($user, 123);
    }

    public function testStartMissionThrowsWhenActivityAlreadyInProgress(): void
    {
        $user = $this->makeUser();
        $mission = (new Mission())
            ->setTitle('m')
            ->setGoldReward(1)
            ->setExpReward(1)
            ->setDurationInSeconds(60)
            ->setEnergyCost(1)
            ->setUser($user);
        $user->setCurrentActivity(new \App\Entity\UserActualActivity());

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn($mission);
        $this->mockTransactionalEm($em, $user, $repo);

        $service = $this->makeService($em);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('activityAlreadyInProgress');
        $service->startMission($user, 1);
    }

    public function testStartMissionThrowsWhenNotEnoughEnergy(): void
    {
        $user = $this->makeUser();
        $user->setEnergyPoints(0);
        $mission = (new Mission())
            ->setTitle('m')
            ->setGoldReward(1)
            ->setExpReward(1)
            ->setDurationInSeconds(60)
            ->setEnergyCost(10)
            ->setUser($user);

        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn($mission);
        $this->mockTransactionalEm($em, $user, $repo);

        $service = $this->makeService($em);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('notEnoughEnergy');
        $service->startMission($user, 1);
    }

    public function testGenerateMissionsForUserCreatesFiveMissionsWithFixedDurationsAndBalancedEconomy(): void
    {
        $persistedMissions = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(static function ($entity) use (&$persistedMissions): void {
            if ($entity instanceof Mission) {
                $persistedMissions[] = $entity;
            }
        });
        $em->method('flush');

        $service = $this->makeService($em);
        $service->generateMissionsForUser($this->makeUser());

        self::assertCount(5, $persistedMissions);
        $durations = array_map(
            static fn (Mission $m) => $m->getDurationInSeconds(),
            $persistedMissions,
        );
        sort($durations);
        self::assertSame([300, 600, 900, 2100, 3600], $durations);

        $titles = array_map(
            static fn (Mission $m) => $m->getTitle(),
            $persistedMissions,
        );
        self::assertSame(\count($titles), \count(array_unique($titles)), 'Five missions must use distinct title keys');

        foreach ($persistedMissions as $mission) {
            self::assertGreaterThan(0, $mission->getGoldReward());
            self::assertGreaterThan(0, $mission->getExpReward());
            self::assertGreaterThan(0, $mission->getEnergyCost());
            self::assertLessThanOrEqual(60, $mission->getEnergyCost());
            self::assertContains($mission->getTitle(), MissionService::titlePool());
        }
    }

    public function testFormatMissionForUserAppliesShopBoosterOnTopOfShipBonus(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $shop = $this->createMock(ShopBoosterSessionService::class);
        $shop->method('pruneExpiredSessions');
        $shop->method('getMissionShopBoosterFraction')->willReturn(0.1);

        $service = new MissionService(
            $em,
            $this->createMock(LevelService::class),
            $this->createMock(ShipMembershipService::class),
            new MissionRewardCalculator($shop),
            new MissionEconomyRoller(),
            new UserWriteLockExecutor($em),
            new TimedActivityLifecycle($em),
            new OwnedTimedActivityResolver($em),
            $this->createMock(DailyChallengeService::class),
        );

        $user = $this->makeUser();
        $mission = (new Mission())
            ->setTitle('t')
            ->setGoldReward(100)
            ->setExpReward(200)
            ->setDurationInSeconds(300)
            ->setEnergyCost(10)
            ->setUser($user);

        $formatted = $service->formatMissionForUser($user, $mission, null);

        self::assertSame(110, $formatted['goldReward']);
        self::assertSame(220, $formatted['expReward']);
        self::assertSame(10, $formatted['shopBoosterPercent']);
    }

    public function testFormatMissionForUserAddsMinimumBumpWhenRoundingWouldLeaveFlatRewards(): void
    {
        $shop = $this->createMock(ShopBoosterSessionService::class);
        $shop->method('pruneExpiredSessions');
        $shop->method('getMissionShopBoosterFraction')->willReturn(0.0);

        $em = $this->createMock(EntityManagerInterface::class);
        $service = new MissionService(
            $em,
            $this->createMock(LevelService::class),
            $this->createMock(ShipMembershipService::class),
            new MissionRewardCalculator($shop),
            new MissionEconomyRoller(),
            new UserWriteLockExecutor($em),
            new TimedActivityLifecycle($em),
            new OwnedTimedActivityResolver($em),
            $this->createMock(DailyChallengeService::class),
        );

        $ship = $this->createMock(Ship::class);
        $ship->method('getMissionsUpgrade')->willReturn(1);

        $user = $this->makeUser();
        $mission = (new Mission())
            ->setTitle('t')
            ->setGoldReward(15)
            ->setExpReward(91)
            ->setDurationInSeconds(300)
            ->setEnergyCost(5)
            ->setUser($user);

        $formatted = $service->formatMissionForUser($user, $mission, $ship);

        self::assertSame(15, $formatted['baseGoldReward']);
        self::assertSame(91, $formatted['baseExpReward']);
        self::assertSame(16, $formatted['goldReward']);
        self::assertSame(92, $formatted['expReward']);
        self::assertSame(1, $formatted['bonusPercent']);
    }

    private function mockTransactionalEm(
        EntityManagerInterface $em,
        User $user,
        EntityRepository $missionRepo,
    ): void {
        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction');
        $connection->method('commit');
        $connection->method('rollBack');

        $em->method('getConnection')->willReturn($connection);
        $em->method('find')->willReturnCallback(
            static function (string $class, mixed $id, ?int $lockMode = null) use ($user) {
                if ($class === User::class && $lockMode === LockMode::PESSIMISTIC_WRITE) {
                    return $user;
                }

                return null;
            }
        );
        $em->method('getRepository')->willReturn($missionRepo);
    }

    private function makeService(EntityManagerInterface $em): MissionService
    {
        return new MissionService(
            $em,
            $this->createMock(LevelService::class),
            $this->createMock(ShipMembershipService::class),
            new MissionRewardCalculator($this->shopBoosterPassthrough()),
            new MissionEconomyRoller(),
            new UserWriteLockExecutor($em),
            new TimedActivityLifecycle($em),
            new OwnedTimedActivityResolver($em),
            $this->createMock(DailyChallengeService::class),
        );
    }

    private function shopBoosterPassthrough(): ShopBoosterSessionService
    {
        $m = $this->createMock(ShopBoosterSessionService::class);
        $m->method('pruneExpiredSessions');
        $m->method('getMissionShopBoosterFraction')->willReturn(0.0);

        return $m;
    }

    private function makeUser(): User
    {
        $level = (new Level())->setName('1')->setExpToNextLevel(220);

        return (new User())
            ->setEmail(sprintf('mission_%s@test.local', bin2hex(random_bytes(4))))
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
}
