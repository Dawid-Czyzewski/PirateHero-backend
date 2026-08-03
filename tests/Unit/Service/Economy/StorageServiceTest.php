<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Economy;

use App\Entity\User;
use App\Entity\UserStorage;
use App\Entity\UserStorageSlot;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Service\Economy\StorageService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class StorageServiceTest extends TestCase
{
    public function testMoveItemThrowsWhenUserHasNoStorage(): void
    {
        $user = (new User())
            ->setEmail(sprintf('st_%s@test.local', bin2hex(random_bytes(3))))
            ->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))))
            ->setPassword('x');

        $service = new StorageService($this->createMock(EntityManagerInterface::class));

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('userStorageNotFound');
        $service->moveItemInStorage($user, 1, 2);
    }

    public function testMoveItemThrowsWhenSlotNumbersInvalid(): void
    {
        $user = (new User())
            ->setEmail(sprintf('st_%s@test.local', bin2hex(random_bytes(3))))
            ->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))))
            ->setPassword('x');

        $storage = new UserStorage();
        $storage->setUser($user);
        $slot = new UserStorageSlot();
        $slot->setSlotNumber(1);
        $slot->setStorage($storage);
        $storage->addSlot($slot);
        $user->setStorage($storage);

        $service = new StorageService($this->createMock(EntityManagerInterface::class));

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('storageSlotNotFound');
        $service->moveItemInStorage($user, 1, 99);
    }
}
