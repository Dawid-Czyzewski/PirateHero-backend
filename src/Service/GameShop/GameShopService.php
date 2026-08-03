<?php

declare(strict_types=1);

namespace App\Service\GameShop;

use App\Entity\User;
use App\Enum\WearableItemType;
use App\Exception\BusinessRuleException;
use App\Mapper\Api\GameShopMapper;
use App\Repository\UserRepository;
use App\Service\Progression\QuestService;
use Doctrine\ORM\EntityManagerInterface;

class GameShopService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameShopItemViewNormalizer $itemNormalizer,
        private readonly UserRepository $userRepository,
        private readonly QuestService $questService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function decodeJsonBody(string $content): array
    {
        $data = json_decode($content, true);

        return \is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function requireBodyInt(array $data, string $key, string $errorCode): int
    {
        if (!isset($data[$key])) {
            throw new BusinessRuleException($errorCode);
        }

        return (int) $data[$key];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function requireBodyString(array $data, string $key, string $errorCode): string
    {
        if (!isset($data[$key]) || !\is_string($data[$key])) {
            throw new BusinessRuleException($errorCode);
        }

        return $data[$key];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function optionalBodyInt(array $data, string $key): ?int
    {
        if (!\array_key_exists($key, $data) || $data[$key] === null) {
            return null;
        }

        return (int) $data[$key];
    }

    /**
     * Shop state plus quest snapshot after a mutating action. Null if the user vanished on reload.
     *
     * @return array{
     *     gold: int,
     *     shop: list<array{
     *         id: int,
     *         nameKey: string,
     *         imageKey: string|null,
     *         slotId: string,
     *         price: int,
     *         rarity: string,
     *         stats: list<array{statId: string, value: int}>,
     *         storeSlotId?: int
     *     }|null>,
     *     inventory: list<array{
     *         id: int,
     *         nameKey: string,
     *         imageKey: string|null,
     *         slotId: string,
     *         price: int,
     *         rarity: string,
     *         stats: list<array{statId: string, value: int}>,
     *         storageSlotId?: int
     *     }|null>,
     *     equipped: array<string, array{
     *         id: int,
     *         nameKey: string,
     *         imageKey: string|null,
     *         slotId: string,
     *         price: int,
     *         rarity: string,
     *         stats: list<array{statId: string, value: int}>
     *     }|null>,
     *     refresh: array{isFreeRefreshAvailable: bool, refreshCost: int},
     *     quests: list<array<string, mixed>>,
     *     hasUnclaimedRewards: bool,
     *     unclaimedCount: int
     * }|null
     */
    public function buildStateWithQuests(string $userId): ?array
    {
        $fresh = $this->reloadUser($userId);
        if ($fresh === null) {
            return null;
        }

        return $this->questService->mergeQuestPayload($this->buildState($fresh), $fresh);
    }

    /**
     * Shop state after a mutating action (no quest merge). Null if the user vanished on reload.
     *
     * @return array{
     *     gold: int,
     *     shop: list<array{
     *         id: int,
     *         nameKey: string,
     *         imageKey: string|null,
     *         slotId: string,
     *         price: int,
     *         rarity: string,
     *         stats: list<array{statId: string, value: int}>,
     *         storeSlotId?: int
     *     }|null>,
     *     inventory: list<array{
     *         id: int,
     *         nameKey: string,
     *         imageKey: string|null,
     *         slotId: string,
     *         price: int,
     *         rarity: string,
     *         stats: list<array{statId: string, value: int}>,
     *         storageSlotId?: int
     *     }|null>,
     *     equipped: array<string, array{
     *         id: int,
     *         nameKey: string,
     *         imageKey: string|null,
     *         slotId: string,
     *         price: int,
     *         rarity: string,
     *         stats: list<array{statId: string, value: int}>
     *     }|null>,
     *     refresh: array{isFreeRefreshAvailable: bool, refreshCost: int}
     * }|null
     */
    public function buildFreshState(string $userId): ?array
    {
        $fresh = $this->reloadUser($userId);
        if ($fresh === null) {
            return null;
        }

        return $this->buildState($fresh);
    }

    /**
     * @return array{
     *     gold: int,
     *     shop: list<array{
     *         id: int,
     *         nameKey: string,
     *         imageKey: string|null,
     *         slotId: string,
     *         price: int,
     *         rarity: string,
     *         stats: list<array{statId: string, value: int}>,
     *         storeSlotId?: int
     *     }|null>,
     *     inventory: list<array{
     *         id: int,
     *         nameKey: string,
     *         imageKey: string|null,
     *         slotId: string,
     *         price: int,
     *         rarity: string,
     *         stats: list<array{statId: string, value: int}>,
     *         storageSlotId?: int
     *     }|null>,
     *     equipped: array<string, array{
     *         id: int,
     *         nameKey: string,
     *         imageKey: string|null,
     *         slotId: string,
     *         price: int,
     *         rarity: string,
     *         stats: list<array{statId: string, value: int}>
     *     }|null>,
     *     refresh: array{isFreeRefreshAvailable: bool, refreshCost: int}
     * }
     */
    public function buildState(User $user): array
    {
        $store = $user->getUserStore();
        $gold = (int) $user->getGold();

        $shop = [];
        $slotByNumber = [];
        if ($store !== null) {
            foreach ($store->getStoreSlots() as $slot) {
                $slotByNumber[(int) $slot->getSlotNumber()] = $slot;
            }
        }
        for ($num = 1; $num <= WearableItemType::SHOP_OFFER_CELL_COUNT; ++$num) {
            $slot = $slotByNumber[$num] ?? null;
            $item = $slot?->getItem();
            $dto = $this->itemNormalizer->normalize($item);
            if ($dto !== null && $slot !== null) {
                $dto['storeSlotId'] = (int) $slot->getId();
            }
            $shop[] = $dto;
        }

        $inventory = [];
        $storage = $user->getStorage();
        if ($storage !== null) {
            $byNum = [];
            foreach ($storage->getSlots() as $slot) {
                $byNum[$slot->getSlotNumber()] = $slot;
            }
            for ($n = 1; $n <= 12; ++$n) {
                $slot = $byNum[$n] ?? null;
                if ($slot === null) {
                    $inventory[] = null;
                    continue;
                }
                $dto = $this->itemNormalizer->normalize($slot->getItem());
                if ($dto !== null) {
                    $dto['storageSlotId'] = (int) $slot->getId();
                }
                $inventory[] = $dto;
            }
        } else {
            $inventory = array_fill(0, 12, null);
        }

        $equipped = [];
        $eq = $user->getUserEquipment();
        if ($eq !== null) {
            foreach ($eq->getUserEquipmentSlots() as $slot) {
                $t = $slot->getType();
                if ($t === null) {
                    continue;
                }
                $equipped[$t->value] = $this->itemNormalizer->normalize($slot->getWearableItem());
            }
        }

        $refresh = [
            'isFreeRefreshAvailable' => $store?->getIsFreeRefreshAvailable() ?? false,
            'refreshCost' => (int) ($store?->getRefreshCost() ?? 1),
        ];

        return GameShopMapper::stateResponse([
            'gold' => $gold,
            'shop' => $shop,
            'inventory' => $inventory,
            'equipped' => $equipped,
            'refresh' => $refresh,
        ])->toArray();
    }

    public function reloadUser(string $userId): ?User
    {
        $this->entityManager->clear();

        return $this->userRepository->find($userId);
    }
}
