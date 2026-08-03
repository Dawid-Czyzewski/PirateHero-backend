<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiWebTestCase;

final class ShipEnrollmentRosterEndpointsTest extends ApiWebTestCase
{
    public function testShipEnrollmentNotFoundCasesWithUnknownShipId(): void
    {
        $client = $this->createAuthenticatedClient();
        $payload = json_encode(['shipId' => 999999], \JSON_THROW_ON_ERROR);

        foreach ([
            '/api/ships/set-invitation-required' => json_encode(['shipId' => 999999, 'requiresInvitation' => true], \JSON_THROW_ON_ERROR),
            '/api/ships/join' => $payload,
            '/api/ships/request-to-join' => $payload,
            '/api/ships/cancel-join-request' => $payload,
        ] as $path => $body) {
            $client->request('POST', $path, [], [], ['CONTENT_TYPE' => 'application/json'], $body);
            self::assertSame(404, $client->getResponse()->getStatusCode(), $path.' should return 404');
            $problem = $this->assertProblemJson($client->getResponse());
            self::assertSame('shipNotFound', $problem['detail']);
        }
    }

    public function testShipRosterEndpointsWithoutShipMembershipReturnForbiddenOrValidation(): void
    {
        $client = $this->createAuthenticatedClient();

        $cases = [
            ['/api/ships/invite-member', json_encode(['username' => 'target'], \JSON_THROW_ON_ERROR)],
            ['/api/ships/remove-member', json_encode(['userId' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'], \JSON_THROW_ON_ERROR)],
            ['/api/ships/transfer-ownership', json_encode(['userId' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'], \JSON_THROW_ON_ERROR)],
            ['/api/ships/change-member-role', json_encode(['userId' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11', 'role' => 'MEMBER'], \JSON_THROW_ON_ERROR)],
            ['/api/ships/cancel-invitation', json_encode(['userId' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'], \JSON_THROW_ON_ERROR)],
            ['/api/ships/approve-join-request', json_encode(['requestId' => 1], \JSON_THROW_ON_ERROR)],
            ['/api/ships/reject-join-request', json_encode(['requestId' => 1], \JSON_THROW_ON_ERROR)],
        ];

        foreach ($cases as [$path, $body]) {
            $client->request('POST', $path, [], [], ['CONTENT_TYPE' => 'application/json'], $body);
            $status = $client->getResponse()->getStatusCode();
            self::assertContains($status, [400, 403, 404, 422], sprintf('%s unexpected %d', $path, $status));
            self::assertNotSame(500, $status);
        }
    }

    public function testShipReadEndpointsWithoutMembershipAreControlled(): void
    {
        $client = $this->createAuthenticatedClient();
        foreach ([
            '/api/ships/my-invitations',
            '/api/ships/my-join-requests',
            '/api/ships/search-users?username=test',
            '/api/ships/messages',
        ] as $path) {
            $client->request('GET', $path);
            $status = $client->getResponse()->getStatusCode();
            self::assertContains($status, [200, 403, 404]);
            self::assertNotSame(500, $status, $path.' should not return 500');
        }
    }
}
