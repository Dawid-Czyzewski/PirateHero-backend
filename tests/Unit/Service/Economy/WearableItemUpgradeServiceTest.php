<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Economy;

use App\Domain\Constants\WearableUpgradeConstants;
use App\Entity\ItemStatistics;
use App\Entity\User;
use App\Entity\UserStorage;
use App\Entity\UserStorageSlot;
use App\Entity\WearableItem;
use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Service\Economy\WearableItemUpgradeService;
use App\Service\Progression\DailyChallengeService;
use App\Service\Progression\WeeklyContractService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class WearableItemUpgradeServiceTest extends TestCase
{
    public function testPreviewReportsNextCostWhileBelowCap(): void
    {
        $item = $this->makeItem(WearableItemRarity::COMMON, 0);
        $service = $this->makeService();

        $preview = $service->preview($item);

        self::assertTrue($preview['canUpgrade']);
        self::assertSame(0, $preview['upgradeLevel']);
        self::assertSame(3, $preview['maxUpgradeLevel']);
        self::assertSame(
            WearableUpgradeConstants::goldCost(0, WearableItemRarity::COMMON),
            $preview['nextCost']
        );
    }

    public function testPreviewAtMaxLevelHasNoNextCost(): void
    {
        $item = $this->makeItem(WearableItemRarity::COMMON, 3);
        $service = $this->makeService();

        $preview = $service->preview($item);

        self::assertFalse($preview['canUpgrade']);
        self::assertNull($preview['nextCost']);
        self::assertSame(3, $preview['upgradeLevel']);
    }

    public function testUpgradeThrowsWhenItemMissing(): void
    {
        $user = $this->makeUserWithChestItem($this->makeItem(WearableItemRarity::COMMON, 0));
        $service = $this->makeService(em: $this->mockTransactionalEm($user, null));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('wearableItemNotFound');
        $service->upgrade($user, 999);
    }

    public function testUpgradeThrowsWhenItemNotOwned(): void
    {
        $item = $this->makeItem(WearableItemRarity::COMMON, 0);
        $this->setEntityId($item, 5);
        $user = $this->makeUserWithChestItem(null);
        $service = $this->makeService(em: $this->mockTransactionalEm($user, $item));

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('itemNotOwned');
        $service->upgrade($user, 5);
    }

    public function testUpgradeThrowsWhenMaxLevelReached(): void
    {
        $item = $this->makeItem(WearableItemRarity::COMMON, 3);
        $this->setEntityId($item, 7);
        $user = $this->makeUserWithChestItem($item);
        $service = $this->makeService(em: $this->mockTransactionalEm($user, $item));

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('itemUpgradeMaxReached');
        $service->upgrade($user, 7);
    }

    public function testUpgradeSpendsGoldAndBumpsStats(): void
    {
        $item = $this->makeItem(WearableItemRarity::COMMON, 0);
        $this->setEntityId($item, 11);
        $user = $this->makeUserWithChestItem($item);
        $goldBefore = $user->getGold();
        $cost = WearableUpgradeConstants::goldCost(0, WearableItemRarity::COMMON);

        $daily = $this->createMock(DailyChallengeService::class);
        $daily->expects(self::once())->method('recordGoldSpent')->with($user, $cost);
        $weekly = $this->createMock(WeeklyContractService::class);
        $weekly->expects(self::once())->method('recordGoldSpent')->with($user, $cost);

        $service = $this->makeService(
            em: $this->mockTransactionalEm($user, $item, withFlush: true, expectCommit: true),
            daily: $daily,
            weekly: $weekly,
        );

        $result = $service->upgrade($user, 11);

        self::assertSame(1, $result['upgradeLevel']);
        self::assertSame($cost, $result['goldSpent']);
        self::assertSame($goldBefore - $cost, $result['gold']);
        self::assertSame($goldBefore - $cost, $user->getGold());
        self::assertSame(6, $item->getStatistics()?->getStrongPoints());
        self::assertSame(4, $item->getStatistics()?->getHealthPoints());
        self::assertSame(10 + $cost, $item->getPrice());
    }

    public function testSpecializeRequiresMaxUpgrade(): void
    {
        $item = $this->makeItem(WearableItemRarity::COMMON, 2);
        $this->setEntityId($item, 20);
        $user = $this->makeUserWithChestItem($item);
        $service = $this->makeService(em: $this->mockTransactionalEm($user, $item));

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('itemSpecializationRequiresMaxUpgrade');
        $service->specialize($user, 20, 'health');
    }

    public function testSpecializeHealthSpendsGoldAndBumpsHp(): void
    {
        $item = $this->makeItem(WearableItemRarity::COMMON, 3);
        $this->setEntityId($item, 21);
        $user = $this->makeUserWithChestItem($item);
        $goldBefore = $user->getGold();
        $cost = WearableUpgradeConstants::specializationGoldCost(WearableItemRarity::COMMON);

        $service = $this->makeService(
            em: $this->mockTransactionalEm($user, $item, withFlush: true, expectCommit: true),
        );

        $result = $service->specialize($user, 21, 'health');

        self::assertSame('health', $result['specialization']);
        self::assertSame($cost, $result['goldSpent']);
        self::assertSame($goldBefore - $cost, $result['gold']);
        self::assertSame(3 + WearableUpgradeConstants::HEALTH_BONUS, $item->getStatistics()?->getHealthPoints());
        self::assertSame('health', $item->getSpecialization());
    }

    public function testSpecializeInvalidType(): void
    {
        $item = $this->makeItem(WearableItemRarity::COMMON, 3);
        $this->setEntityId($item, 22);
        $user = $this->makeUserWithChestItem($item);
        $service = $this->makeService();

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('invalidSpecialization');
        $service->specialize($user, 22, 'nope');
    }

    private function makeService(
        ?EntityManagerInterface $em = null,
        ?DailyChallengeService $daily = null,
        ?WeeklyContractService $weekly = null,
    ): WearableItemUpgradeService {
        return new WearableItemUpgradeService(
            $em ?? $this->createMock(EntityManagerInterface::class),
            $daily ?? $this->createMock(DailyChallengeService::class),
            $weekly ?? $this->createMock(WeeklyContractService::class),
        );
    }

    private function mockTransactionalEm(
        User $user,
        ?WearableItem $item,
        bool $withFlush = false,
        bool $expectCommit = false,
    ): EntityManagerInterface {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        if ($expectCommit) {
            $connection->expects(self::once())->method('commit');
        } else {
            $connection->expects(self::once())->method('rollBack');
        }

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('find')->willReturnCallback(
            static function (string $class, mixed $id, ?int $lockMode = null) use ($user, $item) {
                if ($class === User::class && $lockMode === LockMode::PESSIMISTIC_WRITE) {
                    return $user;
                }
                if ($class === WearableItem::class && $lockMode === LockMode::PESSIMISTIC_WRITE) {
                    return $item;
                }

                return null;
            }
        );
        $em->method('persist');
        if ($withFlush) {
            $em->expects(self::once())->method('flush');
        }

        return $em;
    }

    private function makeUserWithChestItem(?WearableItem $item): User
    {
        $user = (new User())
            ->setEmail('upgrade@test.local')
            ->setUsername('upgrader')
            ->setPassword('hash')
            ->setGold(50_000);

        $storage = new UserStorage();
        $storage->setUser($user);
        $slot = (new UserStorageSlot())->setSlotNumber(1)->setStorage($storage)->setItem($item);
        $storage->addSlot($slot);
        $user->setStorage($storage);

        return $user;
    }

    private function makeItem(WearableItemRarity $rarity, int $upgradeLevel): WearableItem
    {
        $stats = (new ItemStatistics())
            ->setStrongPoints(5)
            ->setAgilityPoints(0)
            ->setCriticalChancePoints(2)
            ->setHealthPoints(3);

        return (new WearableItem())
            ->setName('Helm')
            ->setType(WearableItemType::Helmet)
            ->setRarity($rarity)
            ->setPrice(10)
            ->setStatistics($stats)
            ->setUpgradeLevel($upgradeLevel);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
