<?php

declare(strict_types=1);

namespace App\Service\GameShop;

use App\Entity\ItemStatistics;
use App\Entity\WearableItem;
use App\Enum\WearableItemType;

class GameShopWearableFactory
{
    public function __construct(
        private GameShopOfferRoller $offerRoller,
    ) {
    }

    public function createOfferItem(WearableItemType $type, int $playerLevel = 1): WearableItem
    {
        $tpl = $this->offerRoller->roll($type, $playerLevel);

        $stats = new ItemStatistics();
        $lines = $tpl['shopStats'];
        $strong = 0;
        $agility = 0;
        $health = 0;
        $luck = 0;
        $intelligence = 0;
        foreach ($lines as $row) {
            $sid = $row['statId'];
            $v = (int) $row['value'];
            match ($sid) {
                'strength' => $strong += $v,
                'agility', 'speed' => $agility += $v,
                'health', 'defense' => $health += $v,
                'luck' => $luck += $v,
                'intelligence' => $intelligence += $v,
                default => $health += $v,
            };
        }

        $stats->setStrongPoints($strong);
        $stats->setAgilityPoints($agility);
        $stats->setHealthPoints($health);
        $stats->setCriticalChancePoints($luck);
        $stats->setIntelligencePoints($intelligence);

        $item = new WearableItem();
        $item->setType($type);
        $item->setRarity($tpl['rarity']);
        $item->setPrice($tpl['price']);
        $item->setNameKey($tpl['nameKey']);
        $item->setImageKey($tpl['imageKey']);
        $item->setName($this->fallbackDisplayName($tpl['nameKey']));
        $item->setStatistics($stats);

        return $item;
    }

    private function fallbackDisplayName(string $nameKey): string
    {
        $leaf = substr($nameKey, (int) strrpos($nameKey, '.') + 1);

        return $leaf !== '' ? $leaf : $nameKey;
    }
}
