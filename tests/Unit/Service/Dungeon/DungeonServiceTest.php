<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Dungeon;

use App\Dungeon\DungeonCompletionRewardCalculator;
use App\Dungeon\DungeonStageRewardCalculator;
use App\Entity\Level;
use App\Entity\Mission;
use App\Entity\User;
use App\Entity\UserActualActivity;
use App\Entity\UserDungeonProgress;
use App\Enum\DungeonId;
use App\Exception\BusinessRuleException;
use App\Repository\UserDungeonProgressRepository;
use App\Service\Bestiary\BestiaryService;
use App\Service\Dungeon\DungeonBattleSimulator;
use App\Service\Dungeon\DungeonItemRewardFactory;
use App\Service\Dungeon\DungeonService;
use App\Service\Progression\QuestProgressService;
use App\Service\Progression\TimedActivityGuard;
use App\Service\Progression\TitleService;
use App\Service\ShopBoosters\ShopBoosterSessionService;
use App\Service\User\LevelService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class DungeonServiceTest extends TestCase
{
    public function testFightStageAppliesRewardsOnWin(): void
    {
        $user = $this->makeDungeonUser(gold: 100, exp: 50);

        $progressRepo = $this->createMock(UserDungeonProgressRepository::class);
        $progressRepo->method('findOneForUserDungeon')->willReturn(null);
        $progressRepo->method('getProgressMapForUser')->willReturn(['krypta' => 1]);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->mockTransactionalEntityManager($em, $user);
        $em->expects(self::atLeastOnce())->method('persist');
        $em->expects(self::once())->method('flush');

        $boosterSession = $this->createMock(ShopBoosterSessionService::class);
        $boosterSession->method('getCombatStatistics')->willReturn([
            'strength' => 200,
            'agility' => 200,
            'health' => 200,
            'intelligence' => 50,
            'luck' => 50,
        ]);

        $simulator = $this->createMock(DungeonBattleSimulator::class);
        $simulator->method('simulate')->willReturn([
            'won' => true,
            'logs' => [],
            'playerMaxHp' => 600,
            'opponentMaxHp' => 100,
            'fameEarned' => 0,
            'famePointsChange' => 0,
        ]);

        $levelService = $this->createMock(LevelService::class);
        $levelService->expects(self::once())->method('checkAndUpdateLevel')->with($user);

        $service = $this->makeDungeonService($em, $progressRepo, $boosterSession, $simulator, $levelService);

        $result = $service->fightStage($user, DungeonId::Krypta->value, 1);

        self::assertTrue($result['won']);
        self::assertSame(['gold' => 40, 'exp' => 8], $result['rewards']);
        self::assertSame(140, $user->getGold());
        self::assertSame(58, $user->getExperiencePoints());
        self::assertNotNull($result['updatedUser']);
        self::assertSame(140, $result['updatedUser']['gold']);
    }

    public function testFightStageDoesNotApplyRewardsOnLoss(): void
    {
        $user = $this->makeDungeonUser(gold: 100, exp: 50);

        $progressRepo = $this->createMock(UserDungeonProgressRepository::class);
        $progressRepo->method('findOneForUserDungeon')->willReturn(null);
        $progressRepo->method('getProgressMapForUser')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->mockTransactionalEntityManager($em, $user);
        $em->expects(self::once())->method('flush');

        $boosterSession = $this->createMock(ShopBoosterSessionService::class);
        $boosterSession->method('getCombatStatistics')->willReturn([
            'strength' => 1,
            'agility' => 1,
            'health' => 1,
            'intelligence' => 1,
            'luck' => 1,
        ]);

        $simulator = $this->createMock(DungeonBattleSimulator::class);
        $simulator->method('simulate')->willReturn([
            'won' => false,
            'logs' => [],
            'playerMaxHp' => 3,
            'opponentMaxHp' => 300,
            'fameEarned' => 0,
            'famePointsChange' => 0,
        ]);

        $levelService = $this->createMock(LevelService::class);
        $levelService->expects(self::never())->method('checkAndUpdateLevel');

        $service = $this->makeDungeonService($em, $progressRepo, $boosterSession, $simulator, $levelService);

        $result = $service->fightStage($user, DungeonId::Krypta->value, 1);

        self::assertFalse($result['won']);
        self::assertSame(['gold' => 0, 'exp' => 0], $result['rewards']);
        self::assertNull($result['updatedUser']);
        self::assertSame(100, $user->getGold());
        self::assertSame(50, $user->getExperiencePoints());
        self::assertNotNull($user->getDungeonLostAt());
        self::assertGreaterThan(0, $result['cooldownSecondsRemaining']);
    }

    public function testFightStageBlockedDuringCooldown(): void
    {
        $user = $this->makeDungeonUser(gold: 100, exp: 50);
        $user->setDungeonLostAt(new \DateTimeImmutable('-10 minutes'));

        $service = $this->makeDungeonService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserDungeonProgressRepository::class),
            $this->createMock(ShopBoosterSessionService::class),
            $this->createMock(DungeonBattleSimulator::class),
            $this->createMock(LevelService::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('dungeonCooldownActive');

        $service->fightStage($user, DungeonId::Krypta->value, 1);
    }

    public function testFightStageClearsCooldownOnWin(): void
    {
        $user = $this->makeDungeonUser(gold: 100, exp: 50);
        $user->setDungeonLostAt(new \DateTimeImmutable('-2 hours'));

        $progressRepo = $this->createMock(UserDungeonProgressRepository::class);
        $progressRepo->method('findOneForUserDungeon')->willReturn(null);
        $progressRepo->method('getProgressMapForUser')->willReturn(['krypta' => 1]);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->mockTransactionalEntityManager($em, $user);
        $em->expects(self::atLeastOnce())->method('persist');
        $em->expects(self::once())->method('flush');

        $boosterSession = $this->createMock(ShopBoosterSessionService::class);
        $boosterSession->method('getCombatStatistics')->willReturn([
            'strength' => 200,
            'agility' => 200,
            'health' => 200,
            'intelligence' => 50,
            'luck' => 50,
        ]);

        $simulator = $this->createMock(DungeonBattleSimulator::class);
        $simulator->method('simulate')->willReturn([
            'won' => true,
            'logs' => [],
            'playerMaxHp' => 600,
            'opponentMaxHp' => 100,
            'fameEarned' => 0,
            'famePointsChange' => 0,
        ]);

        $levelService = $this->createMock(LevelService::class);
        $levelService->expects(self::once())->method('checkAndUpdateLevel')->with($user);

        $service = $this->makeDungeonService($em, $progressRepo, $boosterSession, $simulator, $levelService);
        $result = $service->fightStage($user, DungeonId::Krypta->value, 1);

        self::assertTrue($result['won']);
        self::assertNull($user->getDungeonLostAt());
        self::assertSame(0, $result['cooldownSecondsRemaining']);
    }

    public function testFightStageBlockedDuringMission(): void
    {
        $user = $this->makeDungeonUser(gold: 100, exp: 50);
        $activity = new UserActualActivity();
        $activity->setMission(new Mission());
        $user->setCurrentActivity($activity);

        $service = $this->makeDungeonService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserDungeonProgressRepository::class),
            $this->createMock(ShopBoosterSessionService::class),
            $this->createMock(DungeonBattleSimulator::class),
            $this->createMock(LevelService::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('finishMissionFirst');

        $service->fightStage($user, DungeonId::Krypta->value, 1);
    }

    public function testFightStageDoesNotGrantCompletionRewardTwice(): void
    {
        $user = $this->makeDungeonUser(gold: 100, exp: 50);
        $progress = new UserDungeonProgress();
        $progress->setUser($user);
        $progress->setDungeonId(DungeonId::Krypta->value);
        $progress->setClearedStage(9);
        $progress->setCompletionRewardClaimed(true);

        $progressRepo = $this->createMock(UserDungeonProgressRepository::class);
        $progressRepo->method('findOneForUserDungeon')->willReturn($progress);
        $progressRepo->method('getProgressMapForUser')->willReturn(['krypta' => 10]);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->mockTransactionalEntityManager($em, $user);
        $em->expects(self::once())->method('flush');

        $itemFactory = $this->createMock(DungeonItemRewardFactory::class);
        $itemFactory->expects(self::never())->method('grantRandomItem');

        $boosterSession = $this->createMock(ShopBoosterSessionService::class);
        $boosterSession->method('getCombatStatistics')->willReturn([
            'strength' => 200,
            'agility' => 200,
            'health' => 200,
            'intelligence' => 50,
            'luck' => 50,
        ]);

        $simulator = $this->createMock(DungeonBattleSimulator::class);
        $simulator->method('simulate')->willReturn([
            'won' => true,
            'logs' => [],
            'playerMaxHp' => 600,
            'opponentMaxHp' => 100,
            'fameEarned' => 0,
            'famePointsChange' => 0,
        ]);

        $levelService = $this->createMock(LevelService::class);
        $levelService->expects(self::once())->method('checkAndUpdateLevel')->with($user);

        $service = $this->makeDungeonService($em, $progressRepo, $boosterSession, $simulator, $levelService, $itemFactory);

        $result = $service->fightStage($user, DungeonId::Krypta->value, 10);

        self::assertTrue($result['won']);
        self::assertNull($result['completionReward']);
        self::assertFalse($result['dungeonCompleted']);
    }

    private function mockTransactionalEntityManager(EntityManagerInterface $em, User $user): void
    {
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
    }

    private function makeDungeonUser(int $gold, int $exp): User
    {
        $level = (new Level())->setName('15')->setExpToNextLevel(500);
        $user = new User();
        $user->setEmail('dungeon@test.local');
        $user->setUsername('dungeonhero');
        $user->setPassword('hash');
        $user->setLevel($level);
        $user->setGold($gold);
        $user->setExperiencePoints($exp);

        return $user;
    }

    private function makeDungeonService(
        EntityManagerInterface $em,
        UserDungeonProgressRepository $progressRepo,
        ShopBoosterSessionService $boosterSession,
        DungeonBattleSimulator $simulator,
        LevelService $levelService,
        ?DungeonItemRewardFactory $itemFactory = null,
    ): DungeonService {
        return new DungeonService(
            $em,
            $progressRepo,
            $boosterSession,
            $simulator,
            new DungeonStageRewardCalculator(),
            new DungeonCompletionRewardCalculator(),
            $itemFactory ?? $this->createMock(DungeonItemRewardFactory::class),
            $levelService,
            new TimedActivityGuard(),
            $this->createMock(BestiaryService::class),
            $this->createMock(TitleService::class),
            $this->createMock(QuestProgressService::class),
        );
    }
}
