<?php

declare(strict_types=1);

namespace App\Service\GameShop;

use App\Entity\WearableItemTemplate;
use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Domain\WearableRarityWeightedPicker;
use App\Repository\WearableItemTemplateRepository;

final readonly class WearableTemplatePicker
{
    public function __construct(
        private WearableItemTemplateRepository $repository,
    ) {
    }

    public function pickRandomForTypeAndLevel(
        WearableItemType $type,
        int $playerLevel,
        ?WearableItemRarity $rarity = null,
    ): ?WearableItemTemplate {
        $pool = $this->repository->findAvailableForTypeAndLevel($type, $playerLevel);
        if ($rarity !== null) {
            $filtered = array_values(array_filter(
                $pool,
                static fn (WearableItemTemplate $t) => $t->getRarity() === $rarity
            ));
            if ($filtered !== []) {
                $pool = $filtered;
            }
        }

        if ($pool === []) {
            return null;
        }

        if ($rarity !== null) {
            return $pool[random_int(0, \count($pool) - 1)];
        }

        return WearableRarityWeightedPicker::pick(
            $pool,
            static fn (WearableItemTemplate $t) => $t->getRarity()
        );
    }
}
