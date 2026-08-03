<?php

declare(strict_types=1);

namespace App\Service\Economy;

use App\Domain\Constants\EconomyConstants;
use App\Entity\User;
use App\Entity\UserStorage;
use App\Entity\UserStorageSlot;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use Doctrine\ORM\EntityManagerInterface;

class StorageService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function createEmptyStorageForUser(User $user): UserStorage
    {
        $storage = new UserStorage();
        $storage->setUser($user);

        for ($i = 1; $i <= EconomyConstants::STORAGE_SLOT_COUNT; ++$i) {
            $slot = new UserStorageSlot();
            $slot->setSlotNumber($i);
            $slot->setStorage($storage);
            $storage->addSlot($slot);
            $this->entityManager->persist($slot);
        }

        $this->entityManager->persist($storage);
        $this->entityManager->flush();

        return $storage;
    }

    public function moveItemInStorage(User $user, int $fromSlotNumber, int $toSlotNumber): void
    {
        $storage = $user->getStorage();
        if (!$storage) {
            throw new BusinessRuleException('userStorageNotFound');
        }

        $fromSlot = null;
        $toSlot = null;
        foreach ($storage->getSlots() as $slot) {
            if ($slot->getSlotNumber() === $fromSlotNumber) {
                $fromSlot = $slot;
            }
            if ($slot->getSlotNumber() === $toSlotNumber) {
                $toSlot = $slot;
            }
        }

        if (!$fromSlot || !$toSlot) {
            throw new ResourceNotFoundException('storageSlotNotFound');
        }

        $fromItem = $fromSlot->getItem();
        $toItem = $toSlot->getItem();

        $fromSlot->setItem($toItem);
        $toSlot->setItem($fromItem);

        $this->entityManager->persist($fromSlot);
        $this->entityManager->persist($toSlot);
        $this->entityManager->flush();
    }
}
