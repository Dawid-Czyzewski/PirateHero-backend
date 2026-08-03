<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\User;

use App\Entity\Level;
use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Repository\LevelRepository;
use App\Service\Progression\QuestProgressService;
use App\Service\User\LevelService;
use App\Service\User\SkillPointsService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class LevelServiceTest extends TestCase
{
    public function testThrowsWhenUserHasNoLevel(): void
    {
        $user = new User();
        $user->setEmail('lv@test.local')->setUsername('lvu')->setPassword('x');
        $user->setExperiencePoints(0);

        $service = new LevelService(
            $this->createMock(LevelRepository::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(SkillPointsService::class),
            $this->createMock(QuestProgressService::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('userLevelNotFound');
        $service->checkAndUpdateLevel($user);
    }

    public function testNoLevelUpWhenExpBelowThreshold(): void
    {
        $level = (new Level())->setName('3')->setExpToNextLevel(1000);
        $user = (new User())
            ->setEmail('lv2@test.local')
            ->setUsername('lvu2')
            ->setPassword('x')
            ->setLevel($level)
            ->setExperiencePoints(100);

        $service = new LevelService(
            $this->createMock(LevelRepository::class),
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(SkillPointsService::class),
            $this->createMock(QuestProgressService::class),
        );

        $out = $service->checkAndUpdateLevel($user);
        self::assertFalse($out['levelUp']);
        self::assertSame('3', $out['currentLevel']);
        self::assertSame(900, $out['expToNextLevel']);
    }

    public function testLevelUpAppliesNextLevelAndQuestHook(): void
    {
        $current = (new Level())->setName('2')->setExpToNextLevel(100);
        $next = (new Level())->setName('3')->setExpToNextLevel(200);

        $user = (new User())
            ->setEmail('lv3@test.local')
            ->setUsername('lvu3')
            ->setPassword('x')
            ->setLevel($current)
            ->setExperiencePoints(150);

        $levelRepo = $this->createMock(LevelRepository::class);
        $levelRepo->method('findOneBy')->with(['name' => '3'])->willReturn($next);

        $skillPoints = $this->createMock(SkillPointsService::class);
        $skillPoints->expects(self::once())->method('addFreeSkillPoints')->with($user, 5);

        $quests = $this->createMock(QuestProgressService::class);
        $quests->expects(self::once())->method('updateUserLevel')->with($user, 3);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($user);
        $em->expects(self::once())->method('flush');

        $service = new LevelService($levelRepo, $em, $skillPoints, $quests);
        $out = $service->checkAndUpdateLevel($user);

        self::assertTrue($out['levelUp']);
        self::assertSame($next, $user->getLevel());
        self::assertSame(50, $user->getExperiencePoints());
    }

    public function testMultiLevelUpInOneCall(): void
    {
        $level2 = (new Level())->setName('2')->setExpToNextLevel(100);
        $level3 = (new Level())->setName('3')->setExpToNextLevel(200);
        $level4 = (new Level())->setName('4')->setExpToNextLevel(300);

        $user = (new User())
            ->setEmail('lv-multi@test.local')
            ->setUsername('lvumulti')
            ->setPassword('x')
            ->setLevel($level2)
            ->setExperiencePoints(350);

        $levelRepo = $this->createMock(LevelRepository::class);
        $levelRepo->method('findOneBy')->willReturnCallback(static function (array $criteria) use ($level3, $level4) {
            return match ($criteria['name'] ?? null) {
                '3' => $level3,
                '4' => $level4,
                default => null,
            };
        });

        $skillPoints = $this->createMock(SkillPointsService::class);
        $skillPoints->expects(self::exactly(2))->method('addFreeSkillPoints')->with($user, 5);

        $quests = $this->createMock(QuestProgressService::class);
        $quests->expects(self::once())->method('updateUserLevel')->with($user, 4);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($user);
        $em->expects(self::once())->method('flush');

        $service = new LevelService($levelRepo, $em, $skillPoints, $quests);
        $out = $service->checkAndUpdateLevel($user);

        self::assertTrue($out['levelUp']);
        self::assertSame($level4, $user->getLevel());
        // 350 - 100 (to 3) - 200 (to 4) = 50
        self::assertSame(50, $user->getExperiencePoints());
        self::assertSame('4', $out['currentLevel']);
    }

    public function testLevelUpThrowsWhenNextLevelRowMissing(): void
    {
        $current = (new Level())->setName('99')->setExpToNextLevel(10);
        $user = (new User())
            ->setEmail('lv4@test.local')
            ->setUsername('lvu4')
            ->setPassword('x')
            ->setLevel($current)
            ->setExperiencePoints(50);

        $levelRepo = $this->createMock(LevelRepository::class);
        $levelRepo->method('findOneBy')->with(['name' => '100'])->willReturn(null);

        $service = new LevelService(
            $levelRepo,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(SkillPointsService::class),
            $this->createMock(QuestProgressService::class),
        );

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('nextLevelNotFound');
        $service->checkAndUpdateLevel($user);
    }
}
