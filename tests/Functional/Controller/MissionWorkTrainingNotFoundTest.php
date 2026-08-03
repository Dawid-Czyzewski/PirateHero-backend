<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiWebTestCase;

final class MissionWorkTrainingNotFoundTest extends ApiWebTestCase
{
    public function testMissionStartNotFound(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/missions/999999/start');
        self::assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertProblemJson($client->getResponse());
    }

    public function testWorkStartNotFound(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/works/999999/start');
        self::assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertProblemJson($client->getResponse());
    }

    public function testTrainingStartNotFound(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('POST', '/api/trainings/999999/start');
        self::assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertProblemJson($client->getResponse());
    }
}
