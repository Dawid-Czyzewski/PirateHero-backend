<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\MiniGames;

use App\Entity\User;
use App\Enum\CoinFlipSide;
use App\Exception\BusinessRuleException;
use App\Service\MiniGames\CoinFlipRandomInterface;
use App\Service\MiniGames\CoinFlipService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class CoinFlipServiceTest extends TestCase
{
    public function testWinCreditsDoubleStakeOnTopOfReturnedBet(): void
    {
        $user = new User();
        $user->setDiamonds(100);

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

        $random = new class implements CoinFlipRandomInterface {
            public function flip(): CoinFlipSide
            {
                return CoinFlipSide::Heads;
            }
        };

        $service = new CoinFlipService($em, $random);
        $result = $service->play($user, 5, CoinFlipSide::Heads);

        self::assertTrue($result->won);
        self::assertSame(CoinFlipSide::Heads, $result->outcome);
        self::assertSame(10, $result->payoutDiamonds);
        self::assertSame(105, $result->diamondsAfter);
        self::assertSame(105, $user->getDiamonds());
    }

    public function testLoseKeepsStakeDeductedOnly(): void
    {
        $user = new User();
        $user->setDiamonds(100);

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

        $random = new class implements CoinFlipRandomInterface {
            public function flip(): CoinFlipSide
            {
                return CoinFlipSide::Tails;
            }
        };

        $service = new CoinFlipService($em, $random);
        $result = $service->play($user, 5, CoinFlipSide::Heads);

        self::assertFalse($result->won);
        self::assertSame(CoinFlipSide::Tails, $result->outcome);
        self::assertSame(0, $result->payoutDiamonds);
        self::assertSame(95, $result->diamondsAfter);
        self::assertSame(95, $user->getDiamonds());
    }

    public function testStakeBelowMinimumThrows(): void
    {
        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('coinFlipStakeInvalid');

        $service = new CoinFlipService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(CoinFlipRandomInterface::class),
        );
        $service->play(new User(), 0, CoinFlipSide::Heads);
    }

    public function testStakeAboveMaximumThrows(): void
    {
        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('coinFlipStakeInvalid');

        $service = new CoinFlipService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(CoinFlipRandomInterface::class),
        );
        $service->play(new User(), 11, CoinFlipSide::Heads);
    }

    public function testInsufficientDiamondsRollsBack(): void
    {
        $user = new User();
        $user->setDiamonds(3);

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

        $random = $this->createMock(CoinFlipRandomInterface::class);
        $random->expects(self::never())->method('flip');

        $service = new CoinFlipService($em, $random);

        try {
            $service->play($user, 5, CoinFlipSide::Heads);
            self::fail('Expected BusinessRuleException');
        } catch (BusinessRuleException $e) {
            self::assertSame('notEnoughDiamonds', $e->getMessage());
        }

        self::assertSame(3, $user->getDiamonds());
    }
}
