<?php

declare(strict_types=1);

namespace App\Service\Economy;

use App\Domain\Constants\EconomyConstants;
use App\Entity\StoreSlot;
use App\Entity\User;
use App\Entity\UserStorageSlot;
use App\Entity\UserStore;
use App\Entity\WearableItem;
use App\Enum\QuestCategory;
use App\Enum\WearableItemType;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Service\GameShop\GameShopWearableFactory;
use App\Service\Progression\QuestProgressService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class UserStoreService
{
    private const STORE_SLOTS = WearableItemType::SHOP_OFFER_CELL_COUNT;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private QuestProgressService $questProgressService,
        private GameShopWearableFactory $gameShopWearableFactory,
    ) {
    }

    public function ensureUserStore(User $user): UserStore
    {
        $store = $user->getUserStore();
        if ($store !== null) {
            $this->pruneExtraStoreSlots($store);
            $this->backfillMissingStoreSlots($store);

            return $store;
        }

        return $this->createUserStore($user);
    }

    private function pruneExtraStoreSlots(UserStore $store): void
    {
        $em = $this->entityManager;
        $toRemove = [];
        foreach ($store->getStoreSlots() as $slot) {
            if ((int) $slot->getSlotNumber() > self::STORE_SLOTS) {
                $toRemove[] = $slot;
            }
        }
        foreach ($toRemove as $slot) {
            $item = $slot->getItem();
            if ($item !== null) {
                $em->remove($item);
            }
            $store->getStoreSlots()->removeElement($slot);
            $em->remove($slot);
        }
        if ($toRemove !== []) {
            $em->persist($store);
            $em->flush();
        }
    }

    private function backfillMissingStoreSlots(UserStore $store): void
    {
        $em = $this->entityManager;
        $byNum = [];
        foreach ($store->getStoreSlots() as $slot) {
            $byNum[(int) $slot->getSlotNumber()] = $slot;
        }

        $playerLevel = $this->resolvePlayerLevel($store->getUser());
        $changed = false;
        for ($i = 0; $i < self::STORE_SLOTS; ++$i) {
            $num = $i + 1;
            if (isset($byNum[$num])) {
                continue;
            }

            $slot = new StoreSlot();
            $slot->setSlotNumber($num);
            $item = $this->gameShopWearableFactory->createOfferItem(WearableItemType::randomShopOfferType(), $playerLevel);
            $slot->setItem($item);
            $store->addStoreSlot($slot);
            $em->persist($item);
            $em->persist($slot);
            $changed = true;
        }

        if ($changed) {
            $em->persist($store);
            $em->flush();
        }
    }

    /**
     * @param int|null $chestSlotIndexZeroBased target chest cell 0..11; null = first free slot (lowest slot number)
     *
     * @throws BusinessRuleException `notEnoughGold` if the user's gold is below the item price (server-side check; do not rely on the client)
     */
    public function buyItem(User $user, int $storeSlotId, ?int $chestSlotIndexZeroBased = null): void
    {
        $em = $this->entityManager;
        $connection = $em->getConnection();
        $connection->beginTransaction();
        try {
            $lockedUser = $em->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if ($lockedUser === null) {
                throw new ResourceNotFoundException('userNotFound');
            }

            $storeSlot = $em->getRepository(StoreSlot::class)->find($storeSlotId);

            if (!$storeSlot) {
                throw new ResourceNotFoundException('storeSlotNotFound');
            }

            $userStore = $lockedUser->getUserStore();
            if (!$userStore) {
                throw new ResourceNotFoundException('userStoreNotFound');
            }

            if ($storeSlot->getStore()->getId() !== $userStore->getId()) {
                throw new OperationForbiddenException('storeSlotNotYours');
            }

            $item = $storeSlot->getItem();
            if (!$item) {
                throw new BusinessRuleException('storeSlotEmpty');
            }

            $price = (int) $item->getPrice();
            $storage = $lockedUser->getStorage();
            if (!$storage) {
                throw new ResourceNotFoundException('userStorageNotFound');
            }

            $freeSlot = $this->resolveTargetStorageSlot($storage, $chestSlotIndexZeroBased);

            $lockedUser->spendGold($price);
            $freeSlot->setItem($item);
            $storeSlot->setItem(null);

            $em->persist($lockedUser);
            $em->persist($freeSlot);
            $em->persist($storeSlot);
            $em->flush();

            $this->questProgressService->checkAndUpdateProgress($lockedUser, QuestCategory::GOLD_SPENT, $price);
            $rarity = $item->getRarity();
            if ($rarity !== null) {
                $this->questProgressService->recordItemCollected($lockedUser, $rarity);
            }

            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    private function resolveTargetStorageSlot($storage, ?int $chestSlotIndexZeroBased): UserStorageSlot
    {
        if ($chestSlotIndexZeroBased !== null) {
            $num = $chestSlotIndexZeroBased + 1;
            if ($num < 1 || $num > 12) {
                throw new BusinessRuleException('invalidChestSlotIndex');
            }
            foreach ($storage->getSlots() as $s) {
                if ($s->getSlotNumber() === $num) {
                    if ($s->getItem() !== null) {
                        throw new BusinessRuleException('chestSlotOccupied');
                    }

                    return $s;
                }
            }
            throw new ResourceNotFoundException('storageSlotNotFound');
        }

        $candidates = [];
        foreach ($storage->getSlots() as $s) {
            if ($s->getItem() === null) {
                $candidates[] = $s;
            }
        }
        usort($candidates, static fn (UserStorageSlot $a, UserStorageSlot $b) => $a->getSlotNumber() <=> $b->getSlotNumber());
        if ($candidates === []) {
            throw new BusinessRuleException('noFreeStorageSlot');
        }

        return $candidates[0];
    }

    public function getStoreByUserId(string $userId): ?UserStore
    {
        $em = $this->entityManager;

        return $em->getRepository(UserStore::class)->findOneBy([
            'user' => $userId,
        ]);
    }

    public function createUserStore(User $user): UserStore
    {
        $em = $this->entityManager;

        $store = new UserStore();
        $store->setUser($user);
        $store->setIsFreeRefreshAvailable(true);
        $store->setRefreshCost(1);

        $user->setUserStore($store);

        $playerLevel = $this->resolvePlayerLevel($user);
        for ($i = 0; $i < self::STORE_SLOTS; ++$i) {
            $slot = new StoreSlot();
            $slot->setSlotNumber($i + 1);
            $slot->setStore($store);
            $item = $this->gameShopWearableFactory->createOfferItem(WearableItemType::randomShopOfferType(), $playerLevel);
            $slot->setItem($item);
            $em->persist($item);
            $em->persist($slot);
        }

        $em->persist($store);
        $em->persist($user);
        $em->flush();

        return $store;
    }

    private function resolvePlayerLevel(?User $user): int
    {
        if ($user === null) {
            return 1;
        }
        $lvl = $user->getLevel();
        if ($lvl === null) {
            return 1;
        }
        $n = (int) $lvl->getName();

        return max(1, min(100, $n > 0 ? $n : 1));
    }

    public function refreshStore(User $user, bool $ignoreCost = false): void
    {
        $em = $this->entityManager;
        $connection = $em->getConnection();
        $connection->beginTransaction();
        try {
            $lockedUser = $em->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if ($lockedUser === null) {
                throw new ResourceNotFoundException('userNotFound');
            }

            $store = $lockedUser->getUserStore();
            if (!$store) {
                throw new ResourceNotFoundException('userStoreNotFound');
            }

            if (!$ignoreCost) {
                if ($store->getIsFreeRefreshAvailable()) {
                    $store->setIsFreeRefreshAvailable(false);
                } else {
                    $refreshCost = $store->getRefreshCost();
                    $lockedUser->spendDiamonds($refreshCost);
                    $em->persist($lockedUser);
                }
            }

            $playerLevel = $this->resolvePlayerLevel($lockedUser);
            $slotsByNumber = [];
            foreach ($store->getStoreSlots() as $slot) {
                $slotsByNumber[(int) $slot->getSlotNumber()] = $slot;
            }

            for ($i = 0; $i < self::STORE_SLOTS; ++$i) {
                $num = $i + 1;
                $slot = $slotsByNumber[$num] ?? null;
                if ($slot === null) {
                    $slot = new StoreSlot();
                    $slot->setSlotNumber($num);
                    $store->addStoreSlot($slot);
                    $em->persist($slot);
                    $slotsByNumber[$num] = $slot;
                }
                $oldItem = $slot->getItem();
                if ($oldItem) {
                    $em->remove($oldItem);
                }

                $newItem = $this->gameShopWearableFactory->createOfferItem(WearableItemType::randomShopOfferType(), $playerLevel);
                $slot->setItem($newItem);

                $em->persist($newItem);
                $em->persist($slot);
            }

            $em->persist($store);
            $em->flush();
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function sellItem(User $user, int $storageSlotId): void
    {
        $em = $this->entityManager;
        $connection = $em->getConnection();
        $connection->beginTransaction();
        try {
            $lockedUser = $em->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if ($lockedUser === null) {
                throw new ResourceNotFoundException('userNotFound');
            }

            $storage = $lockedUser->getStorage();
            if (!$storage) {
                throw new ResourceNotFoundException('userStorageNotFound');
            }

            $slot = $storage->getSlots()->filter(static fn ($s) => $s->getId() === $storageSlotId)->first();
            if (!$slot) {
                throw new ResourceNotFoundException('storageSlotNotFound');
            }

            $item = $slot->getItem();
            if (!$item) {
                throw new BusinessRuleException('storageSlotEmpty');
            }

            $goldEarned = (int) max(0, floor($item->getPrice() * EconomyConstants::SELL_BACK_RATIO));
            $lockedUser->addGold($goldEarned);

            $slot->setItem(null);

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

    public function getItemFromStorageSlot(User $user, int $storageSlotId): ?WearableItem
    {
        $storage = $user->getStorage();
        if (!$storage) {
            return null;
        }

        $slot = $storage->getSlots()->filter(static fn ($s) => $s->getId() === $storageSlotId)->first();
        if (!$slot) {
            return null;
        }

        return $slot->getItem();
    }
}
