<?php

declare(strict_types=1);

namespace App\Dto\Api\Shop;

final readonly class GameShopStateResponse
{
    /**
     * @param list<array<string, mixed>|null> $shop
     * @param list<array<string, mixed>|null> $inventory
     * @param array<string, array<string, mixed>|null> $equipped
     */
    public function __construct(
        public int $gold,
        public array $shop,
        public array $inventory,
        public array $equipped,
        public GameShopRefreshDto $refresh,
    ) {
    }

    /**
     * @return array{
     *     gold: int,
     *     shop: list<array<string, mixed>|null>,
     *     inventory: list<array<string, mixed>|null>,
     *     equipped: array<string, array<string, mixed>|null>,
     *     refresh: array{isFreeRefreshAvailable: bool, refreshCost: int}
     * }
     */
    public function toArray(): array
    {
        return [
            'gold' => $this->gold,
            'shop' => $this->shop,
            'inventory' => $this->inventory,
            'equipped' => $this->equipped,
            'refresh' => $this->refresh->toArray(),
        ];
    }
}
