<?php

declare(strict_types=1);

namespace App\Mapper\Api;

use App\Dto\Api\Shop\GameShopRefreshDto;
use App\Dto\Api\Shop\GameShopStateResponse;

final readonly class GameShopMapper
{
    /**
     * @param array{
     *     gold: int,
     *     shop: list<array<string, mixed>|null>,
     *     inventory: list<array<string, mixed>|null>,
     *     equipped: array<string, array<string, mixed>|null>,
     *     refresh: array{isFreeRefreshAvailable: bool, refreshCost: int}
     * } $state
     */
    public static function stateResponse(array $state): GameShopStateResponse
    {
        return new GameShopStateResponse(
            gold: $state['gold'],
            shop: $state['shop'],
            inventory: $state['inventory'],
            equipped: $state['equipped'],
            refresh: new GameShopRefreshDto(
                isFreeRefreshAvailable: $state['refresh']['isFreeRefreshAvailable'],
                refreshCost: $state['refresh']['refreshCost'],
            ),
        );
    }
}
