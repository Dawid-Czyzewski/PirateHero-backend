<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Entity\Level;
use App\Entity\User;
use App\Entity\UserWeekendTournament;
use App\Exception\BusinessRuleException;
use App\Repository\UserWeekendTournamentRepository;
use App\Service\Progression\TitleService;
use App\Service\Progression\WeekendTournamentService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class WeekendTournamentServiceTest extends TestCase
{
    public function testRecordWinNoOpOutsideWeekend(): void
    {
        $user = $this->makeUser();
        $repo = $this->createMock(UserWeekendTournamentRepository::class);
        $repo->expects(self::never())->method('findOneForUserEvent');
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('flush');

        $service = new WeekendTournamentService($em, $repo, $this->createMock(TitleService::class));
        $wednesday = new \DateTimeImmutable('2026-10-07');
        self::assertFalse($service->isWeekendOpenNow($wednesday));
        $service->recordWin($user, 1, $wednesday);
    }

    public function testIsWeekendOpenOnlySatSun(): void
    {
        $service = new WeekendTournamentService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserWeekendTournamentRepository::class),
            $this->createMock(TitleService::class),
        );
        self::assertFalse($service->isWeekendOpenNow(new \DateTimeImmutable('2026-10-09'))); // Fri
        self::assertTrue($service->isWeekendOpenNow(new \DateTimeImmutable('2026-10-10')));
        self::assertTrue($service->isWeekendOpenNow(new \DateTimeImmutable('2026-10-11')));
        self::assertFalse($service->isWeekendOpenNow(new \DateTimeImmutable('2026-10-12'))); // Mon
    }

    public function testRecordWinAddsPointsWhenWeekendOpen(): void
    {
        $user = $this->makeUser();
        $saturday = new \DateTimeImmutable('2026-10-10');
        $row = new UserWeekendTournament();
        $row->setUser($user);
        $row->setEventStart($saturday);
        $row->setPoints(0);

        $repo = $this->createMock(UserWeekendTournamentRepository::class);
        $repo->method('findOneForUserEvent')->willReturn($row);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $service = new WeekendTournamentService($em, $repo, $this->createMock(TitleService::class));
        $service->recordWin($user, 1, $saturday);
        self::assertSame(1, $row->getPoints());
    }

    public function testClaimRejectsWhenNotEnoughPoints(): void
    {
        $probe = new WeekendTournamentService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserWeekendTournamentRepository::class),
            $this->createMock(TitleService::class),
        );
        if ($probe->isWeekendOpenNow()) {
            self::markTestSkipped('Claim blocked while weekend is active');
        }

        $user = $this->makeUser();
        $row = new UserWeekendTournament();
        $row->setUser($user);
        $row->setEventStart(new \DateTimeImmutable('saturday last week'));
        $row->setPoints(2);

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

        $repo = $this->createMock(UserWeekendTournamentRepository::class);
        $repo->method('findOneForUserEvent')->willReturn($row);

        $service = new WeekendTournamentService($em, $repo, $this->createMock(TitleService::class));
        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('weekendTournamentNotEnoughPoints');
        $service->claim($user);
    }

    private function makeUser(): User
    {
        return (new User())
            ->setEmail('wt@test.local')
            ->setUsername('wtuser')
            ->setPassword('hash')
            ->setLevel((new Level())->setName('10')->setExpToNextLevel(100))
            ->setGold(100);
    }
}
