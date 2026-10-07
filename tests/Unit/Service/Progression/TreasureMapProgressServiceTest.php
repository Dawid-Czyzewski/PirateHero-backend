<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Entity\Level;
use App\Entity\User;
use App\Entity\UserTreasureMap;
use App\Enum\TreasureMapStepType;
use App\Exception\BusinessRuleException;
use App\Repository\UserTreasureMapRepository;
use App\Service\Economy\WearableRewardFactory;
use App\Service\Progression\TreasureMapProgressService;
use App\Tests\Support\UnconstructedInstance;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class TreasureMapProgressServiceTest extends TestCase
{
    public function testSequentialProgressIgnoresWrongType(): void
    {
        $user = $this->makeUser();
        $map = new UserTreasureMap();
        $map->setUser($user);
        $map->setWeekStart(new \DateTimeImmutable('monday this week'));
        $map->setCurrentStep(1);
        $map->setStepProgress([0, 0, 0, 0, 0]);

        $repo = $this->createMock(UserTreasureMapRepository::class);
        $repo->method('findOneForUserWeek')->willReturn($map);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist');
        $em->method('flush');

        $service = new TreasureMapProgressService(
            $em,
            $repo,
            UnconstructedInstance::of(WearableRewardFactory::class),
        );

        $service->record($user, TreasureMapStepType::ArenaWins, 1);
        self::assertSame(1, $map->getCurrentStep());
        self::assertSame([0, 0, 0, 0, 0], $map->getStepProgress());

        $service->record($user, TreasureMapStepType::Missions, 1);
        self::assertSame(1, $map->getCurrentStep());
        self::assertSame(1, $map->getStepProgress()[0]);

        $service->record($user, TreasureMapStepType::Missions, 1);
        self::assertSame(2, $map->getCurrentStep());
        self::assertTrue($map->getStepProgress()[0] >= 2);
    }

    public function testClaimRequiresComplete(): void
    {
        $user = $this->makeUser();
        $map = new UserTreasureMap();
        $map->setUser($user);
        $map->setWeekStart(new \DateTimeImmutable('monday this week'));
        $map->setCurrentStep(3);
        $map->setStepProgress([2, 1, 0, 0, 0]);
        $map->setCompleted(false);

        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction');
        $connection->expects(self::once())->method('rollBack');

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

        $repo = $this->createMock(UserTreasureMapRepository::class);
        $repo->method('findOneForUserWeek')->willReturn($map);

        $service = new TreasureMapProgressService(
            $em,
            $repo,
            UnconstructedInstance::of(WearableRewardFactory::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('treasureMapNotComplete');
        $service->claimChest($user);
    }

    private function makeUser(): User
    {
        return (new User())
            ->setEmail('tm@test.local')
            ->setUsername('tmuser')
            ->setPassword('hash')
            ->setLevel((new Level())->setName('5')->setExpToNextLevel(100))
            ->setGold(100);
    }
}
