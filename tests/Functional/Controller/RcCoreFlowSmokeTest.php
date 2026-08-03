<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiWebTestCase;

final class RcCoreFlowSmokeTest extends ApiWebTestCase
{
    public function testGameShopStateReturnsEnvelope(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/game-shop/state');
        $body = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertArrayHasKey('shop', $body['data']);
    }

    public function testPremiumShopCatalogAndTransactions(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/users/premium-shop/catalog');
        $catalog = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertNotEmpty($catalog['data']['packs'] ?? $catalog['data']);

        $client->request('GET', '/api/users/premium-shop/transactions');
        $this->assertJsonEnvelopeSuccess($client->getResponse());
    }

    public function testDailyRewardStatusAndClaim(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/users/daily-rewards/status');
        $status = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertArrayHasKey('canClaim', $status['data']);

        if ($status['data']['canClaim'] === true) {
            $client->request('POST', '/api/users/daily-rewards/claim');
            $this->assertJsonEnvelopeSuccess($client->getResponse());
        }
    }

    public function testCoinFlipPlayReturnsEnvelope(): void
    {
        $user = $this->makePersistedActivatedUser();
        $user->setDiamonds(20);
        $this->entityManager()->flush();

        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/games/coin-flip/play',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['stake' => 1, 'choice' => 'heads'], \JSON_THROW_ON_ERROR),
        );

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $body = json_decode($client->getResponse()->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('won', $body['data']);
    }
}
