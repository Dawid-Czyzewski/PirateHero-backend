<?php

declare(strict_types=1);

namespace App\Dto\Api\Shop;

final readonly class GameShopRefreshDto
{
    public function __construct(
        public bool $isFreeRefreshAvailable,
        public int $refreshCost,
    ) {
    }

    public function toArray(): array
    {
        return [
            'isFreeRefreshAvailable' => $this->isFreeRefreshAvailable,
            'refreshCost' => $this->refreshCost,
        ];
    }
}
