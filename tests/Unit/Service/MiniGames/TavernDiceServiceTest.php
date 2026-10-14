<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\MiniGames;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Service\MiniGames\TavernDiceRandomInterface;
use App\Service\MiniGames\TavernDiceService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class TavernDiceServiceTest extends TestCase
{
    public function testWinCreditsFloorPayout(): void
    {
        $user = new User();
        $user->setGold(100);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('commit');
        $connection->expects(self::never())->method('rollBack');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->expects(self::once())->method('flush');
        $em->method('find')
            ->with(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE)
            ->willReturn($user);

        $random = new class implements TavernDiceRandomInterface {
            private int $calls = 0;

            public function roll2d6(): int
            {
                ++$this->calls;

                return $this->calls === 1 ? 10 : 5;
            }
        };

        $service = new TavernDiceService($em, $random);
        $result = $service->play($user, 100);

        self::assertTrue($result->won);
        self::assertFalse($result->tied);
        self::assertSame(10, $result->playerRoll);
        self::assertSame(5, $result->dealerRoll);
        self::assertSame(190, $result->payoutGold);
        self::assertSame(190, $result->goldAfter);
        self::assertSame(190, $user->getGold());
    }

    public function testLoseKeepsStakeDeductedOnly(): void
    {
        $user = new User();
        $user->setGold(100);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('commit');
        $connection->expects(self::never())->method('rollBack');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->expects(self::once())->method('flush');
        $em->method('find')
            ->with(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE)
            ->willReturn($user);

        $random = new class implements TavernDiceRandomInterface {
            private int $calls = 0;

            public function roll2d6(): int
            {
                ++$this->calls;

                return $this->calls === 1 ? 3 : 9;
            }
        };

        $service = new TavernDiceService($em, $random);
        $result = $service->play($user, 50);

        self::assertFalse($result->won);
        self::assertFalse($result->tied);
        self::assertSame(0, $result->payoutGold);
        self::assertSame(50, $result->goldAfter);
        self::assertSame(50, $user->getGold());
    }

    public function testTieRefundsStakeOnly(): void
    {
        $user = new User();
        $user->setGold(100);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('commit');
        $connection->expects(self::never())->method('rollBack');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->expects(self::once())->method('flush');
        $em->method('find')
            ->with(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE)
            ->willReturn($user);

        $random = new class implements TavernDiceRandomInterface {
            public function roll2d6(): int
            {
                return 7;
            }
        };

        $service = new TavernDiceService($em, $random);
        $result = $service->play($user, 40);

        self::assertFalse($result->won);
        self::assertTrue($result->tied);
        self::assertSame(40, $result->payoutGold);
        self::assertSame(100, $result->goldAfter);
        self::assertSame(100, $user->getGold());
    }

    public function testStakeBelowMinimumThrows(): void
    {
        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('tavernDiceStakeInvalid');

        $service = new TavernDiceService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(TavernDiceRandomInterface::class),
        );
        $service->play(new User(), 9);
    }

    public function testStakeAboveMaximumThrows(): void
    {
        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('tavernDiceStakeInvalid');

        $service = new TavernDiceService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(TavernDiceRandomInterface::class),
        );
        $service->play(new User(), 501);
    }

    public function testInsufficientGoldRollsBack(): void
    {
        $user = new User();
        $user->setGold(5);

        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::never())->method('commit');
        $connection->expects(self::once())->method('rollBack');
        $connection->method('isTransactionActive')->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->expects(self::never())->method('flush');
        $em->method('find')
            ->with(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE)
            ->willReturn($user);

        $random = $this->createMock(TavernDiceRandomInterface::class);
        $random->expects(self::never())->method('roll2d6');

        $service = new TavernDiceService($em, $random);

        try {
            $service->play($user, 10);
            self::fail('Expected BusinessRuleException');
        } catch (BusinessRuleException $e) {
            self::assertSame('notEnoughGold', $e->getMessage());
        }

        self::assertSame(5, $user->getGold());
    }
}
