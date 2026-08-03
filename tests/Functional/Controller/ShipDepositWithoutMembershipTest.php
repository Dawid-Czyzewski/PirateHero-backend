<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiWebTestCase;

final class ShipDepositWithoutMembershipTest extends ApiWebTestCase
{
    public function testDepositWithoutShipMembershipReturnsForbiddenOrProblem(): void
    {
        $client = $this->createAuthenticatedClient();
        $client->request(
            'POST',
            '/api/ships/deposit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['gold' => 1, 'diamonds' => null], \JSON_THROW_ON_ERROR)
        );

        $status = $client->getResponse()->getStatusCode();
        self::assertContains($status, [400, 403, 404, 422], 'deposit without club should not succeed as 200');
        self::assertNotSame(500, $status);
    }
}
