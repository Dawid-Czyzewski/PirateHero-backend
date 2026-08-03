<?php

declare(strict_types=1);

namespace App\Service\GameShop;

use App\Config\WearableItemCatalog;
use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Domain\WearableRarityWeightedPicker;
use App\Repository\WearableItemTemplateRepository;
use Psr\Log\LoggerInterface;

final class WearableVariantResolver
{
    public const MAX_TEMPLATE_LEVEL = 75;

    public function __construct(
        private readonly WearableItemTemplateRepository $templateRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return array{
     *   variants: list<array{nameKey: string, imageKey: string, rarity: WearableItemRarity}>,
     *   effectiveLevel: int,
     *   fallbackLevel: int|null
     * }
     */
    public function resolve(WearableItemType $type, int $playerLevel, string $source = 'shop'): array
    {
        [$variants, $fallbackLevel] = $this->resolveVariants($type, $playerLevel);

        if ($fallbackLevel !== null) {
            $this->logger->warning('WearableVariantFallbackTriggered', [
                'source' => $source,
                'type' => $type->value,
                'requestedLevel' => $playerLevel,
                'fallbackLevel' => $fallbackLevel,
            ]);
        }

        $effectiveLevel = $fallbackLevel ?? max(1, $playerLevel);

        return [
            'variants' => $variants,
            'effectiveLevel' => $effectiveLevel,
            'fallbackLevel' => $fallbackLevel,
        ];
    }

    /**
     * @return array{nameKey: string, imageKey: string, rarity: WearableItemRarity}
     */
    public function pickRandomVariant(
        WearableItemType $type,
        int $playerLevel,
        ?WearableItemRarity $rarity = null,
        string $source = 'loot',
    ): array {
        $resolved = $this->resolve($type, $playerLevel, $source);
        $variants = $resolved['variants'];

        if ($rarity !== null) {
            $filtered = array_values(array_filter(
                $variants,
                static fn (array $v) => $v['rarity'] === $rarity
            ));
            if ($filtered !== []) {
                $variants = $filtered;
            }
        }

        if ($variants === []) {
            return WearableItemCatalog::randomForType($type, $rarity, $resolved['effectiveLevel']);
        }

        if ($rarity !== null) {
            return $variants[random_int(0, \count($variants) - 1)];
        }

        return WearableRarityWeightedPicker::pick(
            $variants,
            static fn (array $v) => $v['rarity']
        );
    }

    /**
     * @return array{0: list<array{nameKey: string, imageKey: string, rarity: WearableItemRarity}>, 1: int|null}
     */
    private function resolveVariants(WearableItemType $type, int $playerLevel): array
    {
        $variants = $this->variantsForTypeAndLevel($type, $playerLevel);
        if ($variants === []) {
            $variants = WearableItemCatalog::shopVariantsForType($type, $playerLevel);
        }
        if ($variants !== []) {
            return [$variants, null];
        }

        if ($playerLevel <= self::MAX_TEMPLATE_LEVEL) {
            return [[], null];
        }

        $fallbackLevel = self::MAX_TEMPLATE_LEVEL;
        $variants = $this->variantsForTypeAndLevel($type, $fallbackLevel);
        if ($variants === []) {
            $variants = WearableItemCatalog::shopVariantsForType($type, $fallbackLevel);
        }

        return [$variants, $variants !== [] ? $fallbackLevel : null];
    }

    /**
     * @return list<array{nameKey: string, imageKey: string, rarity: WearableItemRarity}>
     */
    private function variantsForTypeAndLevel(WearableItemType $type, int $playerLevel): array
    {
        $variants = [];
        foreach ($this->templateRepository->findAvailableForTypeAndLevel($type, $playerLevel) as $template) {
            $variants[] = [
                'nameKey' => $template->getNameKey(),
                'imageKey' => $template->getImageKey(),
                'rarity' => $template->getRarity(),
            ];
        }

        return $variants;
    }
}
