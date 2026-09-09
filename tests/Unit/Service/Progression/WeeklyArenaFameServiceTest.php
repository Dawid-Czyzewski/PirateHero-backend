<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Domain\Constants\WeeklyArenaFameConstants;
use App\Entity\Level;
use App\Entity\User;
use App\Entity\UserWeeklyArenaFame;
use App\Exception\BusinessRuleException;
use App\Repository\UserWeeklyArenaFameRepository;
use App\Service\Progression\TitleService;
use App\Service\Progression\WeeklyArenaFameService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class WeeklyArenaFameServiceTest extends TestCase
{
    public function testGetStatusCreatesRow(): void
    {
        $user = $this->makeUser(10);
        $repo = $this->createMock(UserWeeklyArenaFameRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $created = null;
        $repo->method('findOneForUserWeek')->willReturnCallback(static function () use (&$created) {
            return $created;
        });
        $em->method('persist')->willReturnCallback(static function ($entity) use (&$created): void {
            if ($entity instanceof UserWeeklyArenaFame) {
                $created = $entity;
            }
        });
        $em->method('flush');

        $service = new WeeklyArenaFameService($em, $repo, $this->createMock(TitleService::class));
        $status = $service->getStatus($user);

        self::assertSame(0, $status['fameEarned']);
        self::assertSame(0, $status['unclaimedCount']);
        self::assertCount(3, $status['tiers']);
        self::assertSame(90, $status['tiers'][0]['threshold']);
    }

    public function testRecordFameGainedIgnoresNonPositive(): void
    {
        $user = $this->makeUser(5);
        $row = $this->makeRow($user, 0);
        $repo = $this->createMock(UserWeeklyArenaFameRepository::class);
        $repo->method('findOneForUserWeek')->willReturn($row);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $service = new WeeklyArenaFameService($em, $repo, $this->createMock(TitleService::class));
        $service->recordFameGained($user, 0);
        $service->recordFameGained($user, -10);
        self::assertSame(0, $row->getFameEarned());
    }

    public function testRecordFameGainedAddsProgress(): void
    {
        $user = $this->makeUser(5);
        $row = $this->makeRow($user, 0);
        $repo = $this->createMock(UserWeeklyArenaFameRepository::class);
        $repo->method('findOneForUserWeek')->willReturn($row);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $service = new WeeklyArenaFameService($em, $repo, $this->createMock(TitleService::class));
        $service->recordFameGained($user, 30);
        self::assertSame(30, $row->getFameEarned());
    }

    public function testClaimThrowsWhenTierNotReached(): void
    {
        $user = $this->makeUser(10);
        $row = $this->makeRow($user, 50);
        $service = $this->makeServiceForClaim($user, $row, expectCommit: false);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('weeklyArenaFameTierNotReached');
        $service->claimTier($user, 1);
    }

    public function testClaimGrantsRewardsAndTitle(): void
    {
        $user = $this->makeUser(20);
        $row = $this->makeRow($user, 90);
        $goldBefore = $user->getGold();
        $expected = WeeklyArenaFameConstants::claimReward(1, 20);

        $titleService = $this->createMock(TitleService::class);
        $titleService->expects(self::once())
            ->method('grantTitle')
            ->with($user, 'weekly_arena_fighter')
            ->willReturn(true);

        $service = $this->makeServiceForClaim($user, $row, $titleService);
        $result = $service->claimTier($user, 1);

        self::assertTrue($row->isClaimedTier(1));
        self::assertSame($goldBefore + $expected['gold'], $user->getGold());
        self::assertTrue($result['titleGranted']);
        self::assertSame(0, $result['status']['unclaimedCount']);
    }

    private function makeServiceForClaim(
        User $user,
        UserWeeklyArenaFame $row,
        ?TitleService $titleService = null,
        bool $expectCommit = true,
    ): WeeklyArenaFameService {
        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction');
        if ($expectCommit) {
            $connection->expects(self::once())->method('commit');
        } else {
            $connection->expects(self::once())->method('rollBack');
        }

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
        $em->method('flush');

        $repo = $this->createMock(UserWeeklyArenaFameRepository::class);
        $repo->method('findOneForUserWeek')->willReturn($row);

        return new WeeklyArenaFameService(
            $em,
            $repo,
            $titleService ?? $this->createMock(TitleService::class),
        );
    }

    private function makeRow(User $user, int $fame): UserWeeklyArenaFame
    {
        $row = new UserWeeklyArenaFame();
        $row->setUser($user);
        $row->setWeekStart(new \DateTimeImmutable('monday this week'));
        $row->setFameEarned($fame);

        return $row;
    }

    private function makeUser(int $level): User
    {
        $lvl = (new Level())->setName((string) $level)->setExpToNextLevel(100);

        return (new User())
            ->setEmail(sprintf('arena_fame_%s@test.local', bin2hex(random_bytes(3))))
            ->setUsername(sprintf('af_%s', bin2hex(random_bytes(2))))
            ->setPassword('hash')
            ->setLevel($lvl)
            ->setGold(1000)
            ->setdiamonds(10);
    }
}
