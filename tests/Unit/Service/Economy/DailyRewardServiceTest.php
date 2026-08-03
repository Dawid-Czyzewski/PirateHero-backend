<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Economy;

use App\Config\DailyRewardCatalog;
use App\Entity\Level;
use App\Entity\User;
use App\Entity\UserDailyReward;
use App\Entity\UserStorage;
use App\Repository\UserDailyRewardRepository;
use App\Service\Economy\DailyRewardService;
use App\Service\Economy\WearableRewardFactory;
use App\Service\User\LevelService;
use App\Tests\Support\UnconstructedInstance;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class DailyRewardServiceTest extends TestCase
{
    private function createService(
        EntityManagerInterface $em,
        UserDailyRewardRepository $repo,
    ): DailyRewardService {
        $levelService = $this->createMock(LevelService::class);
        $levelService->method('checkAndUpdateLevel')->willReturn(['levelUp' => false]);

        return new DailyRewardService(
            $em,
            $repo,
            $levelService,
            UnconstructedInstance::of(WearableRewardFactory::class),
            new NullLogger(),
        );
    }

    private function createUserWithStorage(): User
    {
        $user = new User();
        $level = new Level();
        $level->setName('1');
        $level->setExpToNextLevel(100);
        $user->setLevel($level);

        $storage = $this->createMock(UserStorage::class);
        $storage->method('getSlots')->willReturn(new \Doctrine\Common\Collections\ArrayCollection());
        $user->setStorage($storage);

        return $user;
    }

    private function createProgress(User $user, int $nextDay, ?\DateTimeInterface $lastClaim): UserDailyReward
    {
        $progress = new UserDailyReward();
        $progress->setUser($user);
        $progress->setNextDay($nextDay);
        $progress->setLastClaimDate($lastClaim);

        return $progress;
    }

    public function testMissedDayResetsToDayOne(): void
    {
        $user = $this->createUserWithStorage();
        $progress = $this->createProgress($user, 12, new \DateTime('-3 days'));

        $repo = $this->createMock(UserDailyRewardRepository::class);
        $repo->method('findOneForUser')->willReturn($progress);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist')->with($progress);
        $em->expects($this->once())->method('flush');

        $service = $this->createService($em, $repo);
        $status = $service->getStatus($user);

        $this->assertSame(1, $progress->getNextDay());
        $this->assertTrue($status['canClaim']);
        $this->assertSame(1, $status['currentDay']);
    }

    public function testYesterdayKeepsProgress(): void
    {
        $user = $this->createUserWithStorage();
        $progress = $this->createProgress($user, 8, new \DateTime('yesterday'));

        $repo = $this->createMock(UserDailyRewardRepository::class);
        $repo->method('findOneForUser')->willReturn($progress);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('flush');

        $service = $this->createService($em, $repo);
        $status = $service->getStatus($user);

        $this->assertSame(8, $progress->getNextDay());
        $this->assertTrue($status['canClaim']);
        $this->assertSame(7, $status['highestClaimedDay']);
    }

    public function testScheduleHasThirtyDays(): void
    {
        $this->assertCount(30, DailyRewardCatalog::getSchedule());
        $this->assertCount(1, DailyRewardCatalog::rewardsForDay(30));
        $this->assertSame('diamonds', DailyRewardCatalog::rewardsForDay(30)[0]['type']);
        $this->assertSame('gold', DailyRewardCatalog::rewardsForDay(4)[0]['type']);
    }
}
