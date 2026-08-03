<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiWebTestCase;

final class EnergyRefillInfoWhenMissionActiveTest extends ApiWebTestCase
{
    public function testGetRefillInfoWhenMissionActiveCannotRefillAndFlagsMission(): void
    {
        $user = $this->makePersistedActivatedUser();
        $user->setEnergyPoints(10);
        $this->entityManager()->flush();

        $mission = $this->makeMissionForUser($user, ['energyCost' => 1, 'durationInSeconds' => 3600]);
        $this->setMissionActivity($user, $mission, new \DateTimeImmutable('-1 minute'));

        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/energy/refill/info');
        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());

        $envelope = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertArrayHasKey('data', $envelope);
        self::assertFalse($envelope['data']['canRefill']);
        self::assertTrue($envelope['data']['hasActiveMission']);
    }
}
