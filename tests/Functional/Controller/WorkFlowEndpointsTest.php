<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiWebTestCase;

final class WorkFlowEndpointsTest extends ApiWebTestCase
{
    public function testStartWorkSuccess(): void
    {
        $user = $this->makePersistedActivatedUser();
        $work = $this->makeWorkForUser($user, ['hoursCount' => 1]);

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/works/'.$work->getId().'/start');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $envelope = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertTrue($envelope['data']['started']);
    }

    public function testCompleteWorkFailsBeforeTimeEnds(): void
    {
        $user = $this->makePersistedActivatedUser();
        $work = $this->makeWorkForUser($user, ['hoursCount' => 5]);
        $this->setWorkActivity($user, $work, new \DateTimeImmutable('now'));

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/works/'.$work->getId().'/complete');
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('workNotComplete', $problem['detail']);
    }

    public function testCancelWorkSuccess(): void
    {
        $user = $this->makePersistedActivatedUser();
        $work = $this->makeWorkForUser($user);
        $this->setWorkActivity($user, $work, new \DateTimeImmutable('-10 minutes'));

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/works/'.$work->getId().'/cancel');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $envelope = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertTrue($envelope['data']['cancelled']);
    }

    public function testWorkOwnershipForbidden(): void
    {
        $owner = $this->makePersistedActivatedUser();
        $other = $this->makePersistedActivatedUser();
        $work = $this->makeWorkForUser($owner);

        $client = $this->createAuthenticatedClient($other);
        $client->request('POST', '/api/works/'.$work->getId().'/start');
        self::assertSame(403, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('workOwnershipRequired', $problem['detail']);
    }

    public function testWorkNotFoundForAllActions(): void
    {
        $client = $this->createAuthenticatedClient();
        foreach (['start', 'cancel', 'complete'] as $action) {
            $client->request('POST', '/api/works/999999/'.$action);
            self::assertSame(404, $client->getResponse()->getStatusCode());
            $problem = $this->assertProblemJson($client->getResponse());
            self::assertSame('workNotFound', $problem['detail']);
        }
    }
}
