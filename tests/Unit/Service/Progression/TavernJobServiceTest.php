<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Domain\Constants\TavernJobConstants;
use App\Entity\Level;
use App\Entity\TavernJob;
use App\Entity\User;
use App\Entity\UserActualActivity;
use App\Exception\BusinessRuleException;
use App\Repository\TavernJobRepository;
use App\Service\Progression\TavernJobService;
use App\Service\Progression\TimedActivity\TimedActivityLifecycle;
use App\Service\Progression\UserWriteLockExecutor;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class TavernJobServiceTest extends TestCase
{
    public function testEnsureDailyOffersCreatesTwoJobs(): void
    {
        $user = $this->makeUser();
        $persisted = [];

        $repo = $this->createMock(TavernJobRepository::class);
        $repo->method('findForUserOnDate')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(static function (object $entity) use (&$persisted): void {
            if ($entity instanceof TavernJob) {
                $persisted[] = $entity;
            }
        });
        $em->expects(self::once())->method('flush');

        $service = $this->makeService($em, $repo);
        $service->ensureDailyOffers($user);

        self::assertCount(TavernJobConstants::DAILY_OFFER_COUNT, $persisted);
        self::assertSame('tavern.job.errand', $persisted[0]->getTitleKey());
        self::assertSame(15, $persisted[0]->getDurationMinutes());
        self::assertSame('tavern.job.cargo', $persisted[1]->getTitleKey());
        self::assertSame(45, $persisted[1]->getDurationMinutes());
        self::assertSame(25, $persisted[0]->getRewardGold());
        self::assertSame(70, $persisted[1]->getRewardGold());
        self::assertSame(1, $persisted[1]->getRewardDiamonds());
    }

    public function testEnsureDailyOffersSkipsWhenAlreadyPresent(): void
    {
        $user = $this->makeUser();
        $existing = [new TavernJob(), new TavernJob()];

        $repo = $this->createMock(TavernJobRepository::class);
        $repo->method('findForUserOnDate')->willReturn($existing);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');
        $em->expects(self::never())->method('flush');

        $service = $this->makeService($em, $repo);
        $service->ensureDailyOffers($user);
    }

    public function testStartMarksJobStartedAndCreatesActivity(): void
    {
        $user = $this->makeUser();
        $job = $this->makeJob($user, 1);

        $repo = $this->createMock(TavernJobRepository::class);
        $repo->method('findForUserOnDate')->willReturn([$job]);
        $repo->method('find')->with(1)->willReturn($job);

        $em = $this->mockTransactionalEm($user);
        $em->expects(self::atLeastOnce())->method('persist');
        $em->expects(self::atLeastOnce())->method('flush');

        $service = $this->makeService($em, $repo);
        $status = $service->start($user, 1);

        self::assertTrue($job->isStarted());
        self::assertNotNull($user->getCurrentActivity());
        self::assertSame($job, $user->getCurrentActivity()?->getTavernJob());
        self::assertNotNull($status['activeJob']);
        self::assertSame(1, $status['activeJob']['id']);
    }

    public function testCompleteAwardsRewardsAfterElapsed(): void
    {
        $user = $this->makeUser();
        $user->setGold(10);
        $user->setDiamonds(0);
        $job = $this->makeJob($user, 1);
        $job->setStarted(true);
        $job->setRewardGold(30);
        $job->setRewardDiamonds(1);
        $job->setDurationMinutes(15);

        $activity = new UserActualActivity();
        $activity->setUser($user);
        $activity->setTavernJob($job);
        $activity->setStartTime(new \DateTime('-20 minutes'));
        $user->setCurrentActivity($activity);

        $repo = $this->createMock(TavernJobRepository::class);
        $repo->method('findForUserOnDate')->willReturn([$job]);

        $em = $this->mockTransactionalEm($user);
        $em->expects(self::once())->method('remove')->with($activity);
        $em->expects(self::atLeastOnce())->method('flush');

        $service = $this->makeService($em, $repo);
        $result = $service->complete($user);

        self::assertTrue($job->isCompleted());
        self::assertNull($user->getCurrentActivity());
        self::assertSame(40, $user->getGold());
        self::assertSame(1, $user->getDiamonds());
        self::assertSame(30, $result['rewardGold']);
        self::assertSame(1, $result['rewardDiamonds']);
    }

    public function testCompleteThrowsWhenNotElapsed(): void
    {
        $user = $this->makeUser();
        $job = $this->makeJob($user, 1);
        $job->setStarted(true);
        $job->setDurationMinutes(45);

        $activity = new UserActualActivity();
        $activity->setUser($user);
        $activity->setTavernJob($job);
        $activity->setStartTime(new \DateTime('-5 minutes'));
        $user->setCurrentActivity($activity);

        $repo = $this->createMock(TavernJobRepository::class);
        $em = $this->mockTransactionalEm($user);

        $service = $this->makeService($em, $repo);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('tavernJobNotComplete');
        $service->complete($user);
    }

    public function testCancelResetsStartedFlag(): void
    {
        $user = $this->makeUser();
        $job = $this->makeJob($user, 1);
        $job->setStarted(true);

        $activity = new UserActualActivity();
        $activity->setUser($user);
        $activity->setTavernJob($job);
        $activity->setStartTime(new \DateTime());
        $user->setCurrentActivity($activity);

        $repo = $this->createMock(TavernJobRepository::class);
        $repo->method('findForUserOnDate')->willReturn([$job]);

        $em = $this->mockTransactionalEm($user);
        $em->expects(self::once())->method('remove')->with($activity);

        $service = $this->makeService($em, $repo);
        $service->cancel($user);

        self::assertFalse($job->isStarted());
        self::assertNull($user->getCurrentActivity());
    }

    private function makeService(EntityManagerInterface $em, TavernJobRepository $repo): TavernJobService
    {
        return new TavernJobService(
            $em,
            $repo,
            new UserWriteLockExecutor($em),
            new TimedActivityLifecycle($em),
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
        $em->method('find')
            ->with(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE)
            ->willReturn($user);

        return $em;
    }

    private function makeUser(): User
    {
        $level = new Level();
        $level->setName('1');

        $user = new User();
        $user->setLevel($level);
        $user->setGold(0);
        $user->setDiamonds(0);

        return $user;
    }

    private function makeJob(User $user, int $id): TavernJob
    {
        $job = new TavernJob();
        $job->setUser($user);
        $job->setTitleKey('tavern.job.errand');
        $job->setDurationMinutes(15);
        $job->setRewardGold(25);
        $job->setRewardDiamonds(0);
        $job->setOfferedOn(new \DateTimeImmutable('today'));

        $property = new \ReflectionProperty($job, 'id');
        $property->setValue($job, $id);

        return $job;
    }
}
