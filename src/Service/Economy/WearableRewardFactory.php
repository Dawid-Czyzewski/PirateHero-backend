<?php

declare(strict_types=1);

namespace App\Service\Economy;

use App\Domain\Constants\EquipmentConstants;
use App\Entity\ItemStatistics;
use App\Entity\User;
use App\Entity\WearableItem;
use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Service\GameShop\WearableItemTemplateService;
use App\Service\Progression\QuestProgressService;
use Doctrine\ORM\EntityManagerInterface;


final readonly class WearableRewardFactory
{
    public const DEFAULT_RARITY_MODIFIERS = EquipmentConstants::RARITY_MODIFIERS;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly WearableItemTemplateService $templateService,
        private readonly QuestProgressService $questProgressService,
    ) {
    }

    public function defaultModifier(WearableItemRarity $rarity): float
    {
        return EquipmentConstants::RARITY_MODIFIERS[$rarity->value];
    }

    /**
     * @param list<WearableItemRarity>|null $allowedRarities when non-empty, picks uniformly; null → RARE
     */
    public function createRandomForUser(
        User $user,
        ?array $allowedRarities = null,
        ?float $modifierOverride = null,
    ): WearableItem {
        if ($allowedRarities !== null && $allowedRarities !== []) {
            $rarity = $allowedRarities[array_rand($allowedRarities)];
        } else {
            $rarity = WearableItemRarity::RARE;
        }

        return $this->createForUser($user, $rarity, $modifierOverride);
    }

    public function createForUser(
        User $user,
        WearableItemRarity $rarity,
        ?float $modifierOverride = null,
    ): WearableItem {
        $level = $user->getLevel() ? (int) $user->getLevel()->getName() : 1;
        $modifier = $modifierOverride ?? $this->defaultModifier($rarity);
        $price = (int) round(
            (EquipmentConstants::PRICE_PER_LEVEL * $level + random_int(0, EquipmentConstants::PRICE_PER_LEVEL * $level)) * $modifier
        );

        $statistics = $this->createItemStats($level, $modifier);
        $this->entityManager->persist($statistics);

        $item = new WearableItem();
        $item->setPrice($price);
        $types = WearableItemType::orderedCases();
        $item->setType($types[array_rand($types)]);
        $item->setRarity($rarity);
        $item->setStatistics($statistics);
        $this->templateService->applyRandomTemplate($item, $level, $item->getType(), $rarity);
        $this->entityManager->persist($item);

        return $item;
    }

    /**
     * Coupon reward_data: required "rarity" and "type"; optional "stats", "name", "price".
     * Preserves coupon-specific fixed stats and legacy intelligence→critical mapping.
     *
     * @param array<string, mixed> $rewardData
     */
    public function createFromRewardData(User $user, array $rewardData): WearableItem
    {
        $level = $user->getLevel() ? (int) $user->getLevel()->getName() : 1;
        $stats = $rewardData['stats'] ?? null;

        $item = new WearableItem();
        $item->setType(WearableItemType::fromLegacyOrSelf(trim((string) $rewardData['type'])));
        $item->setRarity(WearableItemRarity::from(strtoupper(trim((string) $rewardData['rarity']))));
        $item->setName($rewardData['name'] ?? "Reward Item Lv.$level");

        if ($stats && is_array($stats)) {
            $itemStats = new ItemStatistics();
            $strength = (int) ($stats['strength'] ?? $stats['strongPoints'] ?? 0);
            $agility = (int) ($stats['agility'] ?? $stats['agilityPoints'] ?? 0);
            $hasExplicitIntelligence = isset($stats['intelligence']) || isset($stats['intelligencePoints']);
            $intelligence = (int) ($stats['intelligence'] ?? $stats['intelligencePoints'] ?? 0);
            $crit = (int) ($stats['criticalChancePoints'] ?? 0);
            $endurance = (int) ($stats['endurance'] ?? $stats['healthPoints'] ?? 0);

            $itemStats->setStrongPoints($strength);
            $itemStats->setAgilityPoints($agility);
            
            if ($hasExplicitIntelligence) {
                $itemStats->setIntelligencePoints($intelligence);
                $itemStats->setCriticalChancePoints($crit);
            } else {
                $itemStats->setIntelligencePoints(0);
                $itemStats->setCriticalChancePoints($crit > 0 ? $crit : $intelligence);
            }

            $itemStats->setHealthPoints($endurance);
            $item->setStatistics($itemStats);
            $this->entityManager->persist($itemStats);
        } else {
            $rarityModifier = $this->couponRarityModifier($item->getRarity()->value);
            $itemStats = new ItemStatistics();
            $strength = (int) round(random_int(5, 10) * $level * $rarityModifier);
            $agility = (int) round(random_int(5, 10) * $level * $rarityModifier);
            $intelligencePts = (int) round(random_int(1, 3) * $level * $rarityModifier);
            $critPts = (int) round(random_int(1, 3) * $level * $rarityModifier);
            $endurance = (int) round(random_int(10, 20) * $level * $rarityModifier);

            $itemStats->setStrongPoints($strength);
            $itemStats->setAgilityPoints($agility);
            $itemStats->setIntelligencePoints($intelligencePts);
            $itemStats->setCriticalChancePoints($critPts);
            $itemStats->setHealthPoints($endurance);
            $item->setStatistics($itemStats);
            $this->entityManager->persist($itemStats);
        }

        $item->setPrice($rewardData['price'] ?? (int) round(100 * $level));
        $this->entityManager->persist($item);

        return $item;
    }

    private function couponRarityModifier(string $rarity): float
    {
        return match ($rarity) {
            'COMMON' => 1.0,
            'UNCOMMON' => 1.2,
            'RARE' => 1.5,
            'EPIC' => 2.0,
            'LEGENDARY' => 3.0,
            default => 1.0,
        };
    }

    public function createItemStats(int $level, float $modifier): ItemStatistics
    {
        $stats = new ItemStatistics();
        $stats->setStrongPoints((int) round(random_int(
            EquipmentConstants::STRONG_POINTS_MIN,
            EquipmentConstants::STRONG_POINTS_MAX,
        ) * $level * $modifier));
        $stats->setAgilityPoints((int) round(random_int(
            EquipmentConstants::AGILITY_POINTS_MIN,
            EquipmentConstants::AGILITY_POINTS_MAX,
        ) * $level * $modifier));
        $stats->setIntelligencePoints((int) round(random_int(
            EquipmentConstants::INTELLIGENCE_POINTS_MIN,
            EquipmentConstants::INTELLIGENCE_POINTS_MAX,
        ) * $level * $modifier));
        $stats->setCriticalChancePoints((int) round(random_int(
            EquipmentConstants::CRITICAL_CHANCE_POINTS_MIN,
            EquipmentConstants::CRITICAL_CHANCE_POINTS_MAX,
        ) * $level * $modifier));
        $stats->setHealthPoints((int) round(random_int(
            EquipmentConstants::HEALTH_POINTS_MIN,
            EquipmentConstants::HEALTH_POINTS_MAX,
        ) * $level * $modifier));

        return $stats;
    }

    /**
     * @return array<string, mixed>
     */
    public function toClientPayload(WearableItem $item): array
    {
        return [
            'id' => $item->getId(),
            'name' => $item->getName() ?? '',
            'nameKey' => $item->getNameKey(),
            'type' => $item->getType() ? $item->getType()->value : null,
            'rarity' => $item->getRarity() ? $item->getRarity()->value : null,
            'price' => $item->getPrice() ?? 0,
            'imageKey' => $item->getImageKey(),
            'statistics' => $item->getStatistics()?->toClientArray(),
        ];
    }

    public function placeInStorage(User $user, WearableItem $item): void
    {
        $storage = $user->getStorage();
        if (!$storage) {
            throw new ResourceNotFoundException('userStorageNotFound');
        }

        $freeSlot = $storage->getSlots()->filter(static fn ($slot) => $slot->getItem() === null)->first();
        if (!$freeSlot) {
            throw new BusinessRuleException('noFreeStorageSlot');
        }

        $freeSlot->setItem($item);
        $this->entityManager->persist($freeSlot);

        $rarity = $item->getRarity();
        if ($rarity !== null) {
            $this->questProgressService->recordItemCollected($user, $rarity);
        }
    }
}
