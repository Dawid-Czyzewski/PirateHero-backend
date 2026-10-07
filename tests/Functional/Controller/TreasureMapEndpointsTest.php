<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\UserTreasureMap;
use App\Tests\Functional\ApiWebTestCase;

final class TreasureMapEndpointsTest extends ApiWebTestCase
{
    public function testGetStatusCreatesMapEnvelope(): void
    {
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/treasure-map/status');

        self::assertResponseIsSuccessful();
        $data = $this->assertJsonEnvelopeData($client->getResponse());
        self::assertSame(1, $data['currentStep'] ?? null);
        self::assertFalse($data['completed'] ?? true);
        self::assertFalse($data['chestClaimed'] ?? true);
        self::assertCount(5, $data['steps'] ?? []);
        self::assertSame(0, $data['unclaimedCount'] ?? null);
    }

    public function testClaimWithoutCompleteReturnsBusinessError(): void
    {
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/treasure-map/status');
        self::assertResponseIsSuccessful();

        $client->request('POST', '/api/users/treasure-map/claim');
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('treasureMapNotComplete', $problem['detail']);
    }

    public function testClaimWhenCompleteGrantsRewards(): void
    {
        $user = $this->makePersistedActivatedUser();
        $this->ensureUserStorage($user);
        $em = $this->entityManager();
        $goldBefore = $user->getGold();
        $diamondsBefore = (int) ($user->getDiamonds() ?? 0);

        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/treasure-map/status');
        self::assertResponseIsSuccessful();

        $map = $em->getRepository(UserTreasureMap::class)->findOneBy(['user' => $user]);
        self::assertInstanceOf(UserTreasureMap::class, $map);
        $map->setCurrentStep(5);
        $map->setStepProgress([2, 1, 1, 500, 1]);
        $map->setCompleted(true);
        $em->persist($map);
        $em->flush();

        $client->request('POST', '/api/users/treasure-map/claim');
        self::assertResponseIsSuccessful();
        $decoded = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame('treasureMapChestClaimed', $decoded['meta']['message'] ?? null);
        $data = $decoded['data'];
        self::assertGreaterThan($goldBefore, $data['updatedUser']['gold'] ?? 0);
        self::assertGreaterThanOrEqual($diamondsBefore, $data['updatedUser']['diamonds'] ?? 0);
        self::assertTrue($data['status']['chestClaimed'] ?? false);
        self::assertNotEmpty($data['item'] ?? null);
    }
}
