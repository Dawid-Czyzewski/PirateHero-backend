<?php

declare(strict_types=1);

namespace App\Service\Economy;

use App\Domain\Constants\EconomyConstants;
use App\Entity\User;
use App\Entity\UserEquipment;
use App\Entity\UserEquipmentSlot;
use App\Entity\WearableItem;
use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Service\Progression\QuestProgressService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class UserEquipmentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private QuestProgressService $questProgressService,
    ) {
    }

    public function createEmptyEquipmentForUser(User $user): UserEquipment
    {
        $equipment = new UserEquipment();
        $equipment->setUser($user);

        foreach (WearableItemType::orderedCases() as $type) {
            $slot = new UserEquipmentSlot();
            $slot->setType($type);
            $slot->setUserEquipment($equipment);

            $equipment->getUserEquipmentSlots()->add($slot);
            $this->entityManager->persist($slot);
        }

        $this->entityManager->persist($equipment);
        $this->entityManager->flush();

        return $equipment;
    }

    public function ensureUserEquipment(User $user): UserEquipment
    {
        $equipment = $user->getUserEquipment();
        if ($equipment !== null) {
            return $equipment;
        }

        $equipment = $this->createEmptyEquipmentForUser($user);
        $user->setUserEquipment($equipment);

        return $equipment;
    }

    public function equipItem(User $user, int $wearableItemId): void
    {
        $em = $this->entityManager;

        $wearableItem = $em->getRepository(WearableItem::class)->find($wearableItemId);

        if (!$wearableItem) {
            throw new ResourceNotFoundException('wearableItemNotFound');
        }

        $userEquipment = $this->ensureUserEquipment($user);
        $storage = $user->getStorage();

        if (!$storage) {
            throw new ResourceNotFoundException('userStorageNotFound');
        }

        $itemType = $wearableItem->getType();

        $slot = $userEquipment->getUserEquipmentSlots()->filter(static function (UserEquipmentSlot $s) use ($itemType) {
            return $s->getType() === $itemType;
        })->first();

        if (!$slot) {
            throw new BusinessRuleException('equipmentSlotTypeMismatch');
        }

        $storageSlotWithItem = $storage->getSlots()->filter(static function ($s) use ($wearableItem) {
            return $s->getItem() && $s->getItem()->getId() === $wearableItem->getId();
        })->first();

        if (!$storageSlotWithItem) {
            throw new ResourceNotFoundException('itemNotInStorage');
        }

        $currentlyEquippedItem = $slot->getWearableItem();

        if ($currentlyEquippedItem !== null && $currentlyEquippedItem->getId() === $wearableItem->getId()) {
            return;
        }

        if ($currentlyEquippedItem !== null) {
            $storageSlotWithItem->setItem($currentlyEquippedItem);
            $slot->equip($wearableItem);

            $em->persist($storageSlotWithItem);
            $em->persist($slot);
            $em->flush();
            $this->maybeRecordFullEquipment($user);
            $this->maybeRecordRareEquipmentFull($user);

            return;
        }

        $storageSlotWithItem->setItem(null);
        $slot->equip($wearableItem);

        $em->persist($storageSlotWithItem);
        $em->persist($slot);
        $em->flush();
        $this->maybeRecordFullEquipment($user);
        $this->maybeRecordRareEquipmentFull($user);
    }

    public function unequipItem(User $user, string $slotType): void
    {
        $em = $this->entityManager;

        $userEquipment = $this->ensureUserEquipment($user);
        $storage = $user->getStorage();

        if (!$storage) {
            throw new ResourceNotFoundException('userStorageNotFound');
        }

        $normalized = strtolower(trim($slotType));

        $slot = $userEquipment->getUserEquipmentSlots()->filter(
            static function (UserEquipmentSlot $s) use ($normalized) {
                $type = $s->getType();

                return $type !== null && $type->value === $normalized;
            }
        )->first();

        if (!$slot) {
            throw new BusinessRuleException('equipmentSlotNotFound');
        }

        $currentlyEquippedItem = $slot->getWearableItem();

        if ($currentlyEquippedItem === null) {
            throw new BusinessRuleException('noItemEquippedInSlot');
        }

        $freeSlot = $storage->getSlots()->filter(static function ($s) {
            return $s->getItem() === null;
        })->first();

        if ($freeSlot) {
            $freeSlot->setItem($currentlyEquippedItem);
            $slot->unequip();

            $em->persist($freeSlot);
            $em->persist($slot);
            $em->flush();

            return;
        }

        $swappableStorageSlot = $storage->getSlots()->filter(static function ($s) use ($slot) {
            $item = $s->getItem();
            if (!$item) {
                return false;
            }

            return $item->getType() === $slot->getType();
        })->first();

        if ($swappableStorageSlot) {
            $storageItem = $swappableStorageSlot->getItem();

            $swappableStorageSlot->setItem($currentlyEquippedItem);
            $slot->equip($storageItem);

            $em->persist($swappableStorageSlot);
            $em->persist($slot);
            $em->flush();

            return;
        }

        throw new BusinessRuleException('storageSwapNotPossible');
    }

    public function sellEquippedItem(User $user, string $slotType): void
    {
        $em = $this->entityManager;
        $connection = $em->getConnection();
        $connection->beginTransaction();
        try {
            $lockedUser = $em->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if ($lockedUser === null) {
                throw new ResourceNotFoundException('userNotFound');
            }

            $userEquipment = $this->ensureUserEquipment($lockedUser);

            $normalized = strtolower(trim($slotType));

            try {
                $requestedType = WearableItemType::from($normalized);
            } catch (\ValueError) {
                throw new BusinessRuleException('equipmentSlotNotFound');
            }

            $paperDollTypes = WearableItemType::orderedCases();
            if (!\in_array($requestedType, $paperDollTypes, true)) {
                throw new BusinessRuleException('equipmentSlotNotFound');
            }

            $slot = $userEquipment->getUserEquipmentSlots()->filter(
                static function (UserEquipmentSlot $s) use ($normalized) {
                    $type = $s->getType();

                    return $type !== null && $type->value === $normalized;
                }
            )->first();

            if (!$slot) {
                throw new BusinessRuleException('equipmentSlotNotFound');
            }

            $item = $slot->getWearableItem();
            if ($item === null) {
                throw new BusinessRuleException('noItemEquippedInSlot');
            }

            $goldEarned = (int) max(0, floor($item->getPrice() * EconomyConstants::SELL_BACK_RATIO));
            $lockedUser->addGold($goldEarned);

            $item->setUserEquipmentSlot(null);

            $em->persist($lockedUser);
            $em->persist($slot);
            $em->remove($item);
            $em->flush();
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    private function maybeRecordFullEquipment(User $user): void
    {
        if (!$this->isFullSetMeetingMinRarity($user, null)) {
            return;
        }

        $this->questProgressService->recordEquipmentFull($user);
    }

    private function maybeRecordRareEquipmentFull(User $user): void
    {
        if (!$this->isFullSetMeetingMinRarity($user, WearableItemRarity::RARE)) {
            return;
        }

        $this->questProgressService->recordRareEquipmentFull($user);
        $this->maybeRecordEpicEquipmentFull($user);
        $this->maybeRecordLegendaryEquipmentFull($user);
    }

    private function maybeRecordEpicEquipmentFull(User $user): void
    {
        if (!$this->isFullSetMeetingMinRarity($user, WearableItemRarity::EPIC)) {
            return;
        }

        $this->questProgressService->recordEpicEquipmentFull($user);
    }

    private function maybeRecordLegendaryEquipmentFull(User $user): void
    {
        if (!$this->isFullSetMeetingMinRarity($user, WearableItemRarity::LEGENDARY)) {
            return;
        }

        $this->questProgressService->recordLegendaryEquipmentFull($user);
    }

    /**
     * @param WearableItemRarity|null $minRarity null = any equipped item counts; otherwise item rarity must be in the accepted set for that tier
     */
    private function isFullSetMeetingMinRarity(User $user, ?WearableItemRarity $minRarity): bool
    {
        $equipment = $user->getUserEquipment();
        if ($equipment === null) {
            return false;
        }

        $accepted = match ($minRarity) {
            null => null,
            WearableItemRarity::RARE => [
                WearableItemRarity::RARE,
                WearableItemRarity::EPIC,
                WearableItemRarity::LEGENDARY,
            ],
            WearableItemRarity::EPIC => [
                WearableItemRarity::EPIC,
                WearableItemRarity::LEGENDARY,
            ],
            WearableItemRarity::LEGENDARY => [
                WearableItemRarity::LEGENDARY,
            ],
            default => null,
        };

        foreach (WearableItemType::orderedCases() as $type) {
            $slot = $equipment->getUserEquipmentSlots()->filter(
                static fn (UserEquipmentSlot $s) => $s->getType() === $type
            )->first();
            if (!$slot || $slot->getWearableItem() === null) {
                return false;
            }

            if ($accepted === null) {
                continue;
            }

            $rarity = $slot->getWearableItem()->getRarity();
            if ($rarity === null || !\in_array($rarity, $accepted, true)) {
                return false;
            }
        }

        return true;
    }
}
