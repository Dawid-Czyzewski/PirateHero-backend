<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiWebTestCase;

final class UserStoreEndpointsTest extends ApiWebTestCase
{
    public function testBuyItemWithoutStoreSlotIdReturnsValidationError(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/user-store/buy-item', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('storeSlotIdRequired', $problem['detail']);
    }

    public function testSellItemWithoutStorageSlotIdReturnsValidationError(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/user-store/sell-item', [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('storageSlotIdRequired', $problem['detail']);
    }

    public function testRefreshStoreReturnsEitherSuccessOrControlledClientError(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/user-store/refresh');
        $status = $client->getResponse()->getStatusCode();
        self::assertContains($status, [200, 400, 403, 404], $client->getResponse()->getContent());
        self::assertNotSame(500, $status);
    }

    public function testGetStoreByUserEndpointDoesNotCrash(): void
    {
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/user-store/by-user/'.$user->getId());
        $status = $client->getResponse()->getStatusCode();
        self::assertContains($status, [200, 404]);
        self::assertNotSame(500, $status);
        if ($status === 200) {
            $this->assertJsonEnvelopeSuccess($client->getResponse());
        }
    }

    public function testGetStoreByOtherUserIdIsForbidden(): void
    {
        $owner = $this->makePersistedActivatedUser();
        $attacker = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($attacker);
        $client->request('GET', '/api/user-store/by-user/'.$owner->getId());
        self::assertSame(403, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('storeAccessDenied', $problem['detail']);
    }
}
