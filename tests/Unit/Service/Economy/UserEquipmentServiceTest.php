<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Economy;

use App\Entity\User;
use App\Entity\WearableItem;
use App\Exception\ResourceNotFoundException;
use App\Service\Economy\UserEquipmentService;
use App\Service\Progression\QuestProgressService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class UserEquipmentServiceTest extends TestCase
{
    public function testEquipItemThrowsWhenWearableMissing(): void
    {
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->with(9)->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(WearableItem::class)->willReturn($repo);

        $user = $this->createMock(User::class);

        $service = new UserEquipmentService($em, $this->createMock(QuestProgressService::class));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('wearableItemNotFound');
        $service->equipItem($user, 9);
    }

    public function testEnsureUserEquipmentCreatesMissingEquipment(): void
    {
        $item = $this->createMock(WearableItem::class);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn($item);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(WearableItem::class)->willReturn($repo);
        $em->expects($this->atLeastOnce())->method('persist');
        $em->expects($this->atLeastOnce())->method('flush');

        $user = $this->createMock(User::class);
        $user->method('getUserEquipment')->willReturn(null);
        $user->expects($this->once())->method('setUserEquipment');

        $service = new UserEquipmentService($em, $this->createMock(QuestProgressService::class));

        $equipment = $service->ensureUserEquipment($user);
        $this->assertNotNull($equipment);
    }
}
