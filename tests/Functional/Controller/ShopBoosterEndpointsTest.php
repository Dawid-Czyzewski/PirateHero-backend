<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\UserShopBoosterSession;
use App\Service\ShopBoosters\ShopBoosterSessionService;
use App\Tests\Functional\ApiWebTestCase;

final class ShopBoosterEndpointsTest extends ApiWebTestCase
{
    private function ensureShopCatalogInDatabase(): void
    {
        $this->entityManager();
        static::getContainer()->get(ShopBoosterSessionService::class)->seedCatalogIfEmpty();
    }

    public function testCatalogReturnsTwelveItemsWithFrontendShape(): void
    {
        $this->ensureShopCatalogInDatabase();
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/shop-boosters/catalog');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $wrap = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertArrayHasKey('data', $wrap);
        $list = $wrap['data'];
        self::assertCount(12, $list);
        $first = $list[0];
        foreach (['id', 'name', 'description', 'effect', 'durationHours', 'price', 'currency', 'multiplier', 'category'] as $key) {
            self::assertArrayHasKey($key, $first);
        }
        self::assertSame('mis_1', $first['id']);
        self::assertSame('shopBooster.catalog.mis_1.name', $first['name']);
        self::assertSame('shopBooster.catalog.mis_1.description', $first['description']);
        self::assertSame('+5%', $first['effect']);
        self::assertSame('missions', $first['category']);
        self::assertSame('gold', $first['currency']);
        self::assertSame('', $first['multiplier']);
    }

    public function testPurchaseRequiresBoosterId(): void
    {
        $this->ensureShopCatalogInDatabase();
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/shop-boosters/purchase', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('shopBoosterIdRequired', $problem['detail']);
    }

    public function testPurchaseNotFoundForUnknownCode(): void
    {
        $this->ensureShopCatalogInDatabase();
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/shop-boosters/purchase',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['boosterId' => 'invalid'], \JSON_THROW_ON_ERROR),
        );
        self::assertSame(404, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('shopBoosterNotFound', $problem['detail']);
    }

    public function testPurchaseMissionBoosterDeductsGoldAndReturnsSessions(): void
    {
        $this->ensureShopCatalogInDatabase();
        $user = $this->makePersistedActivatedUser();
        $goldBefore = (int) ($user->getGold() ?? 0);
        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/shop-boosters/purchase',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['boosterId' => 'mis_1'], \JSON_THROW_ON_ERROR),
        );
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $wrap = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertArrayHasKey('data', $wrap);
        self::assertArrayHasKey('sessionShopBoosters', $wrap['data']);
        $sessions = $wrap['data']['sessionShopBoosters'];
        self::assertCount(1, $sessions);
        self::assertSame('mis_1', $sessions[0]['boosterId']);
        self::assertIsInt($sessions[0]['expiresAt']);

        $this->entityManager()->refresh($user);
        self::assertSame($goldBefore - 400, (int) ($user->getGold() ?? 0));
    }

    public function testGetUserDataIncludesSessionShopBoosters(): void
    {
        $this->ensureShopCatalogInDatabase();
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/shop-boosters/purchase',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['boosterId' => 'wrk_1'], \JSON_THROW_ON_ERROR),
        );
        self::assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('GET', '/api/users/'.$user->getId());
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $decoded = json_decode($client->getResponse()->getContent(), true);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('data', $decoded);
        self::assertArrayHasKey('sessionShopBoosters', $decoded['data']);
        self::assertIsArray($decoded['data']['sessionShopBoosters']);
        self::assertGreaterThanOrEqual(1, \count($decoded['data']['sessionShopBoosters']));
        $codes = array_column($decoded['data']['sessionShopBoosters'], 'boosterId');
        self::assertContains('wrk_1', $codes);
    }

    public function testPruneExpiredRemovesStaleSessions(): void
    {
        $this->ensureShopCatalogInDatabase();
        $user = $this->makePersistedActivatedUser();
        $em = $this->entityManager();
        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/shop-boosters/purchase',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['boosterId' => 'skl_1'], \JSON_THROW_ON_ERROR),
        );
        self::assertSame(200, $client->getResponse()->getStatusCode());

        $session = $em->getRepository(UserShopBoosterSession::class)->findOneBy(['user' => $user]);
        self::assertInstanceOf(UserShopBoosterSession::class, $session);
        $session->setExpiresAt(new \DateTimeImmutable('-2 hours'));
        $em->flush();

        $client->request(
            'POST',
            '/api/shop-boosters/prune-expired',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{}',
        );
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $wrap = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertArrayHasKey('sessionShopBoosters', $wrap['data']);
        self::assertSame([], $wrap['data']['sessionShopBoosters']);
    }
}
