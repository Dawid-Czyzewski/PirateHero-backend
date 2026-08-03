<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class BroadPostValidationTest extends ApiWebTestCase
{
    public static function strictPostEndpoints(): \Generator
    {
        yield 'ShipEconomyController::depositToShip' => ['/api/ships/deposit'];
        yield 'ShipEconomyController::upgradeShip' => ['/api/ships/upgrade'];
        yield 'FightController::startFight' => ['/api/users/fights/start'];
        yield 'BoosterController::buyBooster' => ['/api/boosters/buy'];
        yield 'CoinFlipController::play' => ['/api/games/coin-flip/play'];
        yield 'AccountChangePasswordController::changePassword' => ['/api/account/change-password'];
        yield 'UserStoreController::buyItem' => ['/api/user-store/buy-item'];
        yield 'Token refresh shape' => ['/api/token/refresh'];
    }

    #[DataProvider('strictPostEndpoints')]
    public function testEmptyJsonIsRejectedWith4xx(string $path): void
    {
        $client = $path === '/api/token/refresh'
            ? static::ensureTestClient()
            : $this->createAuthenticatedClient();
        $client->request('POST', $path, [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        $code = $client->getResponse()->getStatusCode();
        self::assertGreaterThanOrEqual(400, $code, 'Expected client error for empty payload');
        self::assertLessThan(500, $code);
    }
}
