<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Economy;

use App\Entity\ItemStatistics;
use App\Entity\User;
use App\Entity\UserEquipment;
use App\Entity\UserEquipmentSlot;
use App\Entity\UserStorage;
use App\Entity\UserStorageSlot;
use App\Entity\WearableItem;
use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Exception\BusinessRuleException;
use App\Service\Economy\UserEquipmentService;
use App\Service\Progression\QuestProgressService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class UserEquipmentServiceUnequipTest extends TestCase
{
    public function testUnequipResolvesSlotByLowercaseType(): void
    {
        $stats = (new ItemStatistics())
            ->setStrongPoints(0)
            ->setAgilityPoints(0)
            ->setCriticalChancePoints(0)
            ->setHealthPoints(0);
        $item = (new WearableItem())
            ->setName('x')
            ->setPrice(1)
            ->setType(WearableItemType::Helmet)
            ->setRarity(WearableItemRarity::COMMON)
            ->setStatistics($stats);

        $slot = (new UserEquipmentSlot())->setType(WearableItemType::Helmet)->setWearableItem($item);

        $equipment = new UserEquipment();
        $equipment->getUserEquipmentSlots()->add($slot);
        $slot->setUserEquipment($equipment);

        $storageSlot = (new UserStorageSlot())->setSlotNumber(1)->setItem(null);
        $storage = new UserStorage();
        $storage->addSlot($storageSlot);

        $user = $this->createMock(User::class);
        $user->method('getUserEquipment')->willReturn($equipment);
        $user->method('getStorage')->willReturn($storage);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->atLeastOnce())->method('persist');
        $em->expects($this->once())->method('flush');

        $service = new UserEquipmentService($em, $this->createMock(QuestProgressService::class));
        $service->unequipItem($user, 'helmet');
    }

    public function testUnequipThrowsWhenSlotTypeUnknown(): void
    {
        $slot = (new UserEquipmentSlot())->setType(WearableItemType::Helmet);

        $equipment = new UserEquipment();
        $equipment->getUserEquipmentSlots()->add($slot);
        $slot->setUserEquipment($equipment);

        $storage = new UserStorage();
        $storage->addSlot((new UserStorageSlot())->setSlotNumber(1));

        $user = $this->createMock(User::class);
        $user->method('getUserEquipment')->willReturn($equipment);
        $user->method('getStorage')->willReturn($storage);

        $em = $this->createMock(EntityManagerInterface::class);

        $service = new UserEquipmentService($em, $this->createMock(QuestProgressService::class));

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('equipmentSlotNotFound');
        $service->unequipItem($user, 'weapon');
    }
}
