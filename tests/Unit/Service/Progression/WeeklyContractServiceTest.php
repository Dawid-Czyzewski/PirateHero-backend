<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Domain\Constants\WeeklyContractConstants;
use App\Entity\Level;
use App\Entity\User;
use App\Entity\UserWeeklyContract;
use App\Exception\BusinessRuleException;
use App\Repository\UserWeeklyContractRepository;
use App\Service\Progression\TitleCodes;
use App\Service\Progression\TitleService;
use App\Service\Progression\WeeklyContractService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class WeeklyContractServiceTest extends TestCase
{
    public function testGetStatusCreatesContract(): void
    {
        $user = $this->makeUser(25);
        $repo = $this->createMock(UserWeeklyContractRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);

        $created = null;
        $repo->method('findOneForUserWeek')->willReturnCallback(
            function () use (&$created) {
                return $created;
            }
        );
        $em->expects(self::atLeastOnce())->method('persist')->willReturnCallback(
            function ($entity) use (&$created) {
                if ($entity instanceof UserWeeklyContract) {
                    $created = $entity;
                }
            }
        );
        $em->expects(self::atLeastOnce())->method('flush');

        $service = new WeeklyContractService(
            $em,
            $repo,
            $this->createMock(TitleService::class),
        );

        $status = $service->getStatus($user);
        self::assertArrayHasKey('type', $status);
        self::assertArrayHasKey('targetValue', $status);
        self::assertSame(0, $status['progress']);
        self::assertFalse($status['canClaim']);
        self::assertSame(0, $status['unclaimedCount']);
        self::assertContains($status['type'], ['missions', 'arena_wins', 'gold_spent']);
        self::assertSame(
            WeeklyContractConstants::targetForType($status['type'], 25),
            $status['targetValue']
        );
    }

    public function testClaimThrowsWhenContractIncomplete(): void
    {
        $user = $this->makeUser(10);
        $contract = $this->makeContract($user, 'missions', 12, 3);
        $service = $this->makeServiceForClaim($user, $contract, expectCommit: false);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('weeklyContractNotComplete');
        $service->claim($user);
    }

    public function testClaimThrowsWhenAlreadyClaimed(): void
    {
        $user = $this->makeUser(10);
        $contract = $this->makeContract($user, 'missions', 12, 12);
        $contract->setRewardClaimed(true);
        $service = $this->makeServiceForClaim($user, $contract, expectCommit: false);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('weeklyContractAlreadyClaimed');
        $service->claim($user);
    }

    public function testClaimGrantsRewardsAndTitle(): void
    {
        $user = $this->makeUser(20);
        $contract = $this->makeContract($user, 'arena_wins', 12, 12);
        $goldBefore = $user->getGold();
        $diamondsBefore = (int) ($user->getDiamonds() ?? 0);
        $expected = WeeklyContractConstants::claimReward(20);

        $titleService = $this->createMock(TitleService::class);
        $titleService->expects(self::once())
            ->method('grantTitle')
            ->with($user, TitleCodes::WEEKLY_CORSAIR)
            ->willReturn(true);

        $service = $this->makeServiceForClaim($user, $contract, $titleService);
        $result = $service->claim($user);

        self::assertSame($expected['gold'], $result['rewards']['gold']);
        self::assertSame($expected['diamonds'], $result['rewards']['diamonds']);
        self::assertTrue($result['titleGranted']);
        self::assertSame(TitleCodes::WEEKLY_CORSAIR, $result['titleCode']);
        self::assertSame($goldBefore + $expected['gold'], $user->getGold());
        self::assertSame($diamondsBefore + $expected['diamonds'], $user->getDiamonds());
        self::assertTrue($contract->isRewardClaimed());
        self::assertFalse($result['status']['canClaim']);
    }

    public function testRecordMissionsIncrementsMatchingContract(): void
    {
        $user = $this->makeUser(15);
        $contract = $this->makeContract($user, 'missions', 12, 0);
        $repo = $this->createMock(UserWeeklyContractRepository::class);
        $repo->method('findOneForUserWeek')->willReturn($contract);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $service = new WeeklyContractService($em, $repo, $this->createMock(TitleService::class));
        $service->recordMissions($user, 2);

        self::assertSame(2, $contract->getProgress());
    }

    public function testRecordMissionsIgnoresOtherContractType(): void
    {
        $user = $this->makeUser(15);
        $contract = $this->makeContract($user, 'gold_spent', 1200, 0);
        $repo = $this->createMock(UserWeeklyContractRepository::class);
        $repo->method('findOneForUserWeek')->willReturn($contract);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $service = new WeeklyContractService($em, $repo, $this->createMock(TitleService::class));
        $service->recordMissions($user, 1);

        self::assertSame(0, $contract->getProgress());
    }

    public function testRecordGoldSpentIncrementsMatchingContract(): void
    {
        $user = $this->makeUser(15);
        $contract = $this->makeContract($user, 'gold_spent', 1200, 100);
        $repo = $this->createMock(UserWeeklyContractRepository::class);
        $repo->method('findOneForUserWeek')->willReturn($contract);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $service = new WeeklyContractService($em, $repo, $this->createMock(TitleService::class));
        $service->recordGoldSpent($user, 50);

        self::assertSame(150, $contract->getProgress());
    }

    private function makeServiceForClaim(
        User $user,
        UserWeeklyContract $contract,
        ?TitleService $titleService = null,
        bool $expectCommit = true,
    ): WeeklyContractService {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        if ($expectCommit) {
            $connection->expects(self::once())->method('commit');
        } else {
            $connection->expects(self::once())->method('rollBack');
        }

        $repo = $this->createMock(UserWeeklyContractRepository::class);
        $repo->method('findOneForUserWeek')->willReturn($contract);

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
        $em->method('persist');
        if ($expectCommit) {
            $em->expects(self::once())->method('flush');
        } else {
            $em->expects(self::never())->method('flush');
        }

        return new WeeklyContractService(
            $em,
            $repo,
            $titleService ?? $this->createMock(TitleService::class),
        );
    }

    private function makeContract(User $user, string $type, int $target, int $progress): UserWeeklyContract
    {
        $contract = new UserWeeklyContract();
        $contract->setUser($user);
        $contract->setType($type);
        $contract->setTargetValue($target);
        $contract->setProgress($progress);

        return $contract;
    }

    private function makeUser(int $level): User
    {
        $lvl = (new Level())->setName((string) $level)->setExpToNextLevel(100);
        $user = new User();
        $user->setEmail('weekly@test.local');
        $user->setUsername('weeklyhero');
        $user->setPassword('hash');
        $user->setLevel($lvl);
        $user->setGold(1000);
        $user->setDiamonds(5);

        return $user;
    }
}
