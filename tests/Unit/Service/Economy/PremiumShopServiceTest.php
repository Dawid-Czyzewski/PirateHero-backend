<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Economy;

use App\Entity\Level;
use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Repository\PremiumShopTransactionRepository;
use App\Service\Economy\PremiumShopService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class PremiumShopServiceTest extends TestCase
{
    public function testPurchaseThrowsWhenPackUnknown(): void
    {
        $service = new PremiumShopService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(PremiumShopTransactionRepository::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('premiumPackNotFound');
        $service->purchase($this->makeUser(), 'nonexistent-pack');
    }

    public function testPurchaseMintsDiamondsUnderUserLock(): void
    {
        $user = $this->makeUser();
        $user->setDiamonds(10);

        $em = $this->mockTransactionalEm($user);
        $em->expects(self::atLeastOnce())->method('persist');
        $em->expects(self::once())->method('flush');

        $service = new PremiumShopService(
            $em,
            $this->createMock(PremiumShopTransactionRepository::class),
        );

        $result = $service->purchase($user, 'handful');

        self::assertSame(60, $result['updatedUser']['diamonds']);
        self::assertSame('handful', $result['transaction']['packId']);
        self::assertSame(50, $result['transaction']['diamonds']);
    }

    private function mockTransactionalEm(User $user): EntityManagerInterface
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction');
        $connection->method('commit');
        $connection->method('rollBack');

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

        return $em;
    }

    private function makeUser(): User
    {
        $level = (new Level())->setName('1')->setExpToNextLevel(100);

        return (new User())
            ->setEmail(sprintf('ps_%s@test.local', bin2hex(random_bytes(3))))
            ->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))))
            ->setPassword('x')
            ->setLevel($level)
            ->setGold(0)
            ->setDiamonds(0)
            ->setEnergyPoints(100)
            ->setTrainingPoints(10)
            ->setDuelPoints(10)
            ->setFamePoints(0);
    }
}
