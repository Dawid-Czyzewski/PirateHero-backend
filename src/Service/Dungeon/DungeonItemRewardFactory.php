<?php

declare(strict_types=1);

namespace App\Service\Dungeon;

use App\Entity\User;
use App\Entity\WearableItem;
use App\Enum\WearableItemRarity;
use App\Service\Economy\WearableRewardFactory;

class DungeonItemRewardFactory
{
    public function __construct(
        private readonly WearableRewardFactory $wearableRewardFactory,
    ) {
    }

    public function grantRandomItem(User $user, string $rarityValue = 'RARE'): WearableItem
    {
        $rarity = WearableItemRarity::tryFrom($rarityValue) ?? WearableItemRarity::RARE;
        $modifier = $this->wearableRewardFactory->defaultModifier($rarity);

        $item = $this->wearableRewardFactory->createForUser($user, $rarity, $modifier);
        $this->wearableRewardFactory->placeInStorage($user, $item);

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    public function itemToClientPayload(WearableItem $item): array
    {
        return $this->wearableRewardFactory->toClientPayload($item);
    }

    /**
     * @return array{id: int|string, slots: list<array<string, mixed>>}|null
     */
    public function storageToClientPayload(User $user): ?array
    {
        $storage = $user->getStorage();
        if ($storage === null) {
            return null;
        }

        $slots = [];
        foreach ($storage->getSlots() as $slot) {
            $item = $slot->getItem();
            $slots[] = [
                'id' => $slot->getId(),
                'slotNumber' => $slot->getSlotNumber(),
                'item' => $item !== null ? $this->itemToClientPayload($item) : null,
            ];
        }

        return [
            'id' => $storage->getId(),
            'slots' => $slots,
        ];
    }
}
