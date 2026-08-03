<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiWebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class BoosterRankingShipChatFunctionalTest extends ApiWebTestCase
{
    public function testPlayersRankingReturnsItemsAndPagination(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/rankings/players?page=1&limit=5');
        $payload = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertArrayHasKey('items', $payload['data']);
        self::assertArrayHasKey('pagination', $payload['data']);
        self::assertIsArray($payload['data']['items']);
        self::assertArrayHasKey('page', $payload['data']['pagination']);
    }

    public function testShipsRankingReturnsItemsAndPagination(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/rankings/ships?page=1&limit=5');
        $payload = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertArrayHasKey('items', $payload['data']);
        self::assertArrayHasKey('pagination', $payload['data']);
    }

    public function testAvailableBoostersReturnsEnvelope(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/boosters/available');
        $this->assertJsonEnvelopeSuccess($client->getResponse());
    }

    public function testBuyBoosterRequiresIdInBody(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/boosters/buy',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([], \JSON_THROW_ON_ERROR),
        );
        self::assertSame(Response::HTTP_BAD_REQUEST, $client->getResponse()->getStatusCode());
        $this->assertProblemJson($client->getResponse());
    }

    public function testShipMessagesRequiresMembership(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/ships/messages');
        $response = $client->getResponse();
        self::assertNotSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertProblemJson($response);
    }
}
