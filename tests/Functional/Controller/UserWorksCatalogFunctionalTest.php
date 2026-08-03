<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Service\Progression\WorkService;
use App\Tests\Functional\ApiWebTestCase;

final class UserWorksCatalogFunctionalTest extends ApiWebTestCase
{
    public function testGetUserDataReturnsFiveWorksAfterCatalogGeneration(): void
    {
        $user = $this->makePersistedActivatedUser();
        $workService = static::getContainer()->get(WorkService::class);
        self::assertInstanceOf(WorkService::class, $workService);
        $workService->generateWorksForUser($user);

        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/'.$user->getId());

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $envelope = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertArrayHasKey('works', $envelope['data']);
        self::assertCount(WorkService::WORK_OFFER_COUNT, $envelope['data']['works']);

        $titles = array_map(
            static fn (array $row): string => (string) $row['title'],
            $envelope['data']['works']
        );
        self::assertContains('work.kitchen_helper', $titles);
        self::assertContains('work.port_dockhand', $titles);
        self::assertContains('work.tavern_server', $titles);
    }
}
