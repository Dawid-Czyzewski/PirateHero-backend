<?php

declare(strict_types=1);

namespace App\Dungeon;


final readonly class DungeonCompletionReward
{
    public function __construct(
        public int $gold,
        public int $diamonds,
        public bool $grantsItem,
        public string $itemRarity = 'RARE',
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->gold === 0 && $this->diamonds === 0 && !$this->grantsItem;
    }

    /**
     * @return array{gold: int, diamonds: int, grantsItem: bool, itemRarity: string}
     */
    public function toArray(): array
    {
        return [
            'gold' => $this->gold,
            'diamonds' => $this->diamonds,
            'grantsItem' => $this->grantsItem,
            'itemRarity' => $this->itemRarity,
        ];
    }
}
