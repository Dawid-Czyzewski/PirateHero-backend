<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Economy;

use App\Entity\Level;
use App\Entity\StoreSlot;
use App\Entity\User;
use App\Entity\UserStorage;
use App\Entity\UserStorageSlot;
use App\Entity\UserStore;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Service\Economy\UserStoreService;
use App\Service\GameShop\GameShopWearableFactory;
use App\Service\Progression\QuestProgressService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class UserStoreServiceTest extends TestCase
{
    public function testBuyItemThrowsWhenStoreSlotNotFound(): void
    {
        $user = $this->makeUser();
        $em = $this->createMock(EntityManagerInterface::class);
        $storeRepo = $this->createMock(EntityRepository::class);
        $storeRepo->method('find')->willReturn(null);
        $this->mockTransactionalEm($em, $user, $storeRepo);
        $service = new UserStoreService($em, $this->createMock(QuestProgressService::class), $this->createMock(GameShopWearableFactory::class));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('storeSlotNotFound');
        $service->buyItem($user, 1);
    }

    public function testBuyItemThrowsWhenNoUserStore(): void
    {
        $user = $this->makeUser();
        $slot = new StoreSlot();

        $em = $this->createMock(EntityManagerInterface::class);
        $storeRepo = $this->createMock(EntityRepository::class);
        $storeRepo->method('find')->willReturn($slot);
        $this->mockTransactionalEm($em, $user, $storeRepo);
        $service = new UserStoreService($em, $this->createMock(QuestProgressService::class), $this->createMock(GameShopWearableFactory::class));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('userStoreNotFound');
        $service->buyItem($user, 1);
    }

    public function testBuyItemThrowsWhenSlotNotOwned(): void
    {
        $user = $this->makeUser();
        $userStore = (new UserStore())->setRefreshCost(1)->setIsFreeRefreshAvailable(true)->setUser($user);
        $this->setEntityId($userStore, 1);
        $user->setUserStore($userStore);

        $otherStore = (new UserStore())->setRefreshCost(1)->setIsFreeRefreshAvailable(true)->setUser($this->makeUser());
        $this->setEntityId($otherStore, 2);
        $item = (new \App\Entity\WearableItem())->setName('i')->setPrice(10)->setType(\App\Enum\WearableItemType::Helmet)->setRarity(\App\Enum\WearableItemRarity::COMMON)->setStatistics((new \App\Entity\ItemStatistics())->setStrongPoints(1)->setAgilityPoints(1)->setCriticalChancePoints(1)->setHealthPoints(1));
        $slot = (new StoreSlot())->setSlotNumber(1)->setStore($otherStore)->setItem($item);

        $em = $this->createMock(EntityManagerInterface::class);
        $storeRepo = $this->createMock(EntityRepository::class);
        $storeRepo->method('find')->willReturn($slot);
        $this->mockTransactionalEm($em, $user, $storeRepo);
        $service = new UserStoreService($em, $this->createMock(QuestProgressService::class), $this->createMock(GameShopWearableFactory::class));

        $this->expectException(OperationForbiddenException::class);
        $this->expectExceptionMessage('storeSlotNotYours');
        $service->buyItem($user, 1);
    }

    public function testBuyItemThrowsWhenNoFreeStorageSlot(): void
    {
        $user = $this->makeUser();
        $store = (new UserStore())->setRefreshCost(1)->setIsFreeRefreshAvailable(true)->setUser($user);
        $user->setUserStore($store);

        $item = (new \App\Entity\WearableItem())->setName('i')->setPrice(10)->setType(\App\Enum\WearableItemType::Helmet)->setRarity(\App\Enum\WearableItemRarity::COMMON)->setStatistics((new \App\Entity\ItemStatistics())->setStrongPoints(1)->setAgilityPoints(1)->setCriticalChancePoints(1)->setHealthPoints(1));
        $slot = (new StoreSlot())->setSlotNumber(1)->setStore($store)->setItem($item);

        $storage = new UserStorage();
        $storage->setUser($user);
        $filled = (new UserStorageSlot())->setSlotNumber(1)->setStorage($storage)->setItem($item);
        $storage->addSlot($filled);
        $user->setStorage($storage);

        $em = $this->createMock(EntityManagerInterface::class);
        $storeRepo = $this->createMock(EntityRepository::class);
        $storeRepo->method('find')->willReturn($slot);
        $this->mockTransactionalEm($em, $user, $storeRepo);
        $service = new UserStoreService($em, $this->createMock(QuestProgressService::class), $this->createMock(GameShopWearableFactory::class));

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('noFreeStorageSlot');
        $service->buyItem($user, 1);
    }

    public function testRefreshStoreThrowsWhenNotEnoughDiamonds(): void
    {
        $user = $this->makeUser();
        $user->setDiamonds(1);
        $store = (new UserStore())->setRefreshCost(5)->setIsFreeRefreshAvailable(false)->setUser($user);
        $user->setUserStore($store);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->mockTransactionalEmOnly($em, $user);
        $service = new UserStoreService($em, $this->createMock(QuestProgressService::class), $this->createMock(GameShopWearableFactory::class));

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('notEnoughDiamonds');
        $service->refreshStore($user);
    }

    public function testSellItemCreditsGoldUnderUserLock(): void
    {
        $user = $this->makeUser();
        $item = (new \App\Entity\WearableItem())->setName('i')->setPrice(100)->setType(\App\Enum\WearableItemType::Helmet)->setRarity(\App\Enum\WearableItemRarity::COMMON)->setStatistics((new \App\Entity\ItemStatistics())->setStrongPoints(1)->setAgilityPoints(1)->setCriticalChancePoints(1)->setHealthPoints(1));
        $storage = new UserStorage();
        $storage->setUser($user);
        $slot = (new UserStorageSlot())->setSlotNumber(1)->setStorage($storage)->setItem($item);
        $this->setEntityId($slot, 42);
        $storage->addSlot($slot);
        $user->setStorage($storage);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->mockTransactionalEmOnly($em, $user);
        $em->expects(self::once())->method('flush');

        $service = new UserStoreService($em, $this->createMock(QuestProgressService::class), $this->createMock(GameShopWearableFactory::class));
        $service->sellItem($user, 42);

        self::assertSame(150, $user->getGold());
    }

    private function mockTransactionalEmOnly(EntityManagerInterface $em, User $user): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction');
        $connection->method('commit');
        $connection->method('rollBack');

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
        $em->method('remove');
    }

    private function mockTransactionalEm(
        EntityManagerInterface $em,
        User $user,
        EntityRepository $storeRepo,
    ): void {
        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction');
        $connection->method('commit');
        $connection->method('rollBack');

        $em->method('getConnection')->willReturn($connection);
        $em->method('find')->willReturnCallback(
            static function (string $class, mixed $id, ?int $lockMode = null) use ($user) {
                if ($class === User::class && $lockMode === LockMode::PESSIMISTIC_WRITE) {
                    return $user;
                }

                return null;
            }
        );
        $em->method('getRepository')->willReturn($storeRepo);
    }

    private function makeUser(): User
    {
        $level = (new Level())->setName('1')->setExpToNextLevel(220);

        return (new User())
            ->setEmail(sprintf('store_%s@test.local', bin2hex(random_bytes(4))))
            ->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))))
            ->setPassword('hash')
            ->setLevel($level)
            ->setGold(100)
            ->setDiamonds(10)
            ->setEnergyPoints(100)
            ->setTrainingPoints(10)
            ->setDuelPoints(10)
            ->setFamePoints(10);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
