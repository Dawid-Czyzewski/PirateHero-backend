<?php

declare(strict_types=1);

namespace App\Tests\Functional\Contract;

use App\Tests\Functional\ApiWebTestCase;

final class ProblemJsonErrorContractTest extends ApiWebTestCase
{
    public function testNotFoundUsesProblemPlusJson(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request('GET', '/api/users/a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a22');
        self::assertSame(404, $client->getResponse()->getStatusCode());
        $this->assertProblemJson($client->getResponse());
    }
}
