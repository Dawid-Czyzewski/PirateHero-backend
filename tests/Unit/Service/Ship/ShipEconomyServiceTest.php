<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Ship;

use App\Entity\Level;
use App\Entity\Ship;
use App\Entity\ShipMember;
use App\Entity\User;
use App\Enum\ShipRole;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Repository\ShipMemberRepository;
use App\Repository\ShipUpgradeLevelCostRepository;
use App\Service\Ship\ShipChatService;
use App\Service\Ship\ShipEconomyService;
use App\Service\Ship\ShipUpgradePricingService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ShipEconomyServiceTest extends TestCase
{
    private function pricingService(): ShipUpgradePricingService
    {
        $repo = $this->createMock(ShipUpgradeLevelCostRepository::class);
        $repo->method('findOneBy')->willReturn(null);

        return new ShipUpgradePricingService($repo);
    }

    private function createEntityManagerMock(
        int $flushTimes = 1,
        bool $expectCommit = false,
        int $rollbackTimes = 0,
    ): EntityManagerInterface {
        $conn = $this->createMock(Connection::class);
        $conn->method('isTransactionActive')->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($conn);
        $em->expects(self::once())->method('beginTransaction');
        $em->method('lock');
        $em->expects(self::exactly($flushTimes))->method('flush');
        if ($expectCommit) {
            $em->expects(self::once())->method('commit');
        } else {
            $em->expects(self::never())->method('commit');
        }
        $em->expects(self::exactly($rollbackTimes))->method('rollback');

        return $em;
    }

    public function testDepositThrowsWhenNotMember(): void
    {
        $repo = $this->createMock(ShipMemberRepository::class);
        $repo->method('findOneBy')->willReturn(null);

        $em = $this->createEntityManagerMock(0, false, 1);

        $chat = $this->createMock(ShipChatService::class);
        $chat->expects(self::never())->method('publishDepositSystemMessage');

        $service = new ShipEconomyService($em, $repo, $chat, $this->pricingService());

        $this->expectException(OperationForbiddenException::class);
        $this->expectExceptionMessage('shipMembershipRequired');
        $service->depositToShip(new Ship(), $this->makeUser(), 10, 0);
    }

    public function testDepositThrowsWhenNothingToDeposit(): void
    {
        $member = new ShipMember();

        $repo = $this->createMock(ShipMemberRepository::class);
        $repo->method('findOneBy')->willReturn($member);

        $em = $this->createEntityManagerMock(0, false, 1);

        $chat = $this->createMock(ShipChatService::class);
        $chat->expects(self::never())->method('publishDepositSystemMessage');

        $service = new ShipEconomyService($em, $repo, $chat, $this->pricingService());

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('nothingToDeposit');
        $service->depositToShip(new Ship(), $this->makeUser(), 0, 0);
    }

    public function testUpgradeShipThrowsOnInvalidType(): void
    {
        $em = $this->createEntityManagerMock(0, false, 1);

        $chat = $this->createMock(ShipChatService::class);
        $service = new ShipEconomyService($em, $this->createMock(ShipMemberRepository::class), $chat, $this->pricingService());

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('invalidUpgradeType');
        $service->upgradeShip(new Ship(), 'unknown');
    }

    public function testUpgradeSkillsFromLevelZeroDeductsBaseCosts(): void
    {
        $ship = (new Ship())
            ->setTitle('Test')
            ->setGold(10_000)
            ->setDiamonds(10_000)
            ->setSkillsUpgrade(0);

        $em = $this->createEntityManagerMock(1, true, 0);

        $chat = $this->createMock(ShipChatService::class);
        $chat->expects(self::once())->method('publishUpgradeSystemMessage')->with($ship, 'skills', 1);
        $service = new ShipEconomyService($em, $this->createMock(ShipMemberRepository::class), $chat, $this->pricingService());

        $result = $service->upgradeShip($ship, 'skills');

        self::assertSame('skills', $result['upgradeType']);
        self::assertSame(1, $result['newLevel']);
        self::assertSame(150, $result['goldCost']);
        self::assertSame(0, $result['diamondsCost']);
        self::assertSame(9850, $ship->getGold());
        self::assertSame(10_000, $ship->getDiamonds());
        self::assertSame(1, $ship->getSkillsUpgrade());
    }

    public function testUpgradeHullIncreasesMaxMembers(): void
    {
        $ship = (new Ship())
            ->setTitle('HullTest')
            ->setGold(10_000)
            ->setDiamonds(10_000)
            ->setMaxMembers(10)
            ->setHullUpgrade(0);

        $em = $this->createEntityManagerMock(1, true, 0);

        $chat = $this->createMock(ShipChatService::class);
        $chat->expects(self::once())->method('publishUpgradeSystemMessage')->with($ship, 'hull', 1);
        $service = new ShipEconomyService($em, $this->createMock(ShipMemberRepository::class), $chat, $this->pricingService());

        $result = $service->upgradeShip($ship, 'hull');

        self::assertSame('hull', $result['upgradeType']);
        self::assertSame(1, $result['newLevel']);
        self::assertSame(1, $ship->getHullUpgrade());
        self::assertSame(11, $ship->getMaxMembers());
    }

    public function testDepositMovesGoldFromUserToShip(): void
    {
        $user = $this->makeUser();
        $ship = (new Ship())->setTitle('C')->setGold(0)->setDiamonds(0);

        $member = (new ShipMember())
            ->setUser($user)
            ->setShip($ship)
            ->setRole(ShipRole::MEMBER);

        $repo = $this->createMock(ShipMemberRepository::class);
        $repo->method('findOneBy')->willReturn($member);

        $em = $this->createEntityManagerMock(1, true, 0);

        $chat = $this->createMock(ShipChatService::class);
        $chat->expects(self::once())->method('publishDepositSystemMessage')->with($ship, $user, 100, 0);

        $service = new ShipEconomyService($em, $repo, $chat, $this->pricingService());
        $service->depositToShip($ship, $user, 100, 0);

        self::assertSame(900, $user->getGold());
        self::assertSame(100, $ship->getGold());
    }

    private function makeUser(): User
    {
        $level = (new Level())->setName('1')->setExpToNextLevel(100);

        return (new User())
            ->setEmail(sprintf('ce_%s@test.local', bin2hex(random_bytes(3))))
            ->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))))
            ->setPassword('x')
            ->setLevel($level)
            ->setGold(1000)
            ->setDiamonds(100)
            ->setEnergyPoints(100)
            ->setTrainingPoints(10)
            ->setDuelPoints(10)
            ->setFamePoints(0);
    }
}
