<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiWebTestCase;

final class MissionFlowEndpointsTest extends ApiWebTestCase
{
    public function testStartMissionSuccessConsumesEnergy(): void
    {
        $user = $this->makePersistedActivatedUser();
        $mission = $this->makeMissionForUser($user, [
            'energyCost' => 7,
            'durationInSeconds' => 60,
        ]);
        $beforeEnergy = (int) $user->getEnergyPoints();

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/missions/'.$mission->getId().'/start');
        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        $envelope = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertTrue($envelope['data']['started']);

        $em = $this->entityManager();
        $em->refresh($user);
        self::assertSame($beforeEnergy - 7, $user->getEnergyPoints());
        self::assertNotNull($user->getCurrentActivity());
    }

    public function testStartMissionFailsWhenNotEnoughEnergy(): void
    {
        $user = $this->makePersistedActivatedUser();
        $user->setEnergyPoints(0);
        $this->entityManager()->flush();
        $mission = $this->makeMissionForUser($user, ['energyCost' => 10]);

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/missions/'.$mission->getId().'/start');
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('notEnoughEnergy', $problem['detail']);
    }

    public function testCancelMissionSuccessRefundsEnergy(): void
    {
        $user = $this->makePersistedActivatedUser();
        $mission = $this->makeMissionForUser($user, ['energyCost' => 9]);
        $user->setEnergyPoints(20);
        $this->entityManager()->flush();
        $this->setMissionActivity($user, $mission, new \DateTimeImmutable('-1 minute'));
        $user->setEnergyPoints(11);
        $this->entityManager()->flush();

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/missions/'.$mission->getId().'/cancel');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $envelope = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertTrue($envelope['data']['cancelled']);

        $em = $this->entityManager();
        $em->refresh($user);
        self::assertNull($user->getCurrentActivity());
        self::assertSame(20, $user->getEnergyPoints());
    }

    public function testCompleteMissionFailsBeforeDurationEnds(): void
    {
        $user = $this->makePersistedActivatedUser();
        $mission = $this->makeMissionForUser($user, [
            'durationInSeconds' => 9999,
            'energyCost' => 1,
        ]);
        $this->setMissionActivity($user, $mission, new \DateTimeImmutable('now'));

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/missions/'.$mission->getId().'/complete');
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('missionNotComplete', $problem['detail']);
    }

    public function testCompleteMissionSuccessGrantsRewardsAndResetsActivity(): void
    {
        $user = $this->makePersistedActivatedUser();
        $user->setGold(1000);
        $user->setExperiencePoints(1000);
        $this->entityManager()->flush();

        $mission = $this->makeMissionForUser($user, [
            'durationInSeconds' => 1,
            'goldReward' => 120,
            'expReward' => 80,
            'energyCost' => 1,
        ]);
        $this->setMissionActivity($user, $mission, new \DateTimeImmutable('-2 minutes'));

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/missions/'.$mission->getId().'/complete');
        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        $envelope = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertArrayHasKey('earnedGold', $envelope['data']);
        self::assertArrayHasKey('earnedExp', $envelope['data']);
        self::assertArrayHasKey('missions', $envelope['data']);

        $em = $this->entityManager();
        $em->refresh($user);
        self::assertNull($user->getCurrentActivity());
        self::assertSame(120, $envelope['data']['earnedGold']);
        self::assertSame(80, $envelope['data']['earnedExp']);
    }

    public function testMissionOwnershipForbiddenForAnotherUser(): void
    {
        $owner = $this->makePersistedActivatedUser();
        $other = $this->makePersistedActivatedUser();
        $mission = $this->makeMissionForUser($owner);

        $client = $this->createAuthenticatedClient($other);
        $client->request('POST', '/api/missions/'.$mission->getId().'/start');
        self::assertSame(403, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('missionOwnershipRequired', $problem['detail']);
    }

    public function testMissionNotFoundForStartCancelComplete(): void
    {
        $client = $this->createAuthenticatedClient();
        foreach (['start', 'cancel', 'complete', 'skip'] as $action) {
            $client->request('POST', '/api/missions/999999/'.$action);
            self::assertSame(404, $client->getResponse()->getStatusCode(), "Expected 404 for action {$action}");
            $problem = $this->assertProblemJson($client->getResponse());
            self::assertSame('missionNotFound', $problem['detail']);
        }
    }

    public function testSkipMissionSpendsDiamondsAndMakesReadyToClaim(): void
    {
        $user = $this->makePersistedActivatedUser();
        $user->setGold(1000);
        $user->setExperiencePoints(1000);
        $user->setDiamonds(20);
        $this->entityManager()->flush();

        $mission = $this->makeMissionForUser($user, [
            'durationInSeconds' => 3600,
            'goldReward' => 50,
            'expReward' => 40,
            'energyCost' => 1,
        ]);
        $this->setMissionActivity($user, $mission, new \DateTimeImmutable('-30 seconds'));

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/missions/'.$mission->getId().'/skip');
        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        $envelope = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame(5, $envelope['data']['diamondsSpent']);
        self::assertTrue($envelope['data']['readyToClaim']);
        self::assertArrayHasKey('startTime', $envelope['data']);

        $em = $this->entityManager();
        $em->refresh($user);
        self::assertNotNull($user->getCurrentActivity());
        self::assertSame(15, $user->getDiamonds());
        self::assertSame(1000, $user->getGold());

        $client->request('POST', '/api/missions/'.$mission->getId().'/complete');
        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        $complete = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame(50, $complete['data']['earnedGold']);
    }

    public function testSkipMissionCostScalesWithShortRemaining(): void
    {
        $user = $this->makePersistedActivatedUser();
        $user->setGold(1000);
        $user->setExperiencePoints(1000);
        $user->setDiamonds(10);
        $this->entityManager()->flush();

        $mission = $this->makeMissionForUser($user, [
            'durationInSeconds' => 120,
            'goldReward' => 50,
            'expReward' => 40,
            'energyCost' => 1,
        ]);
        $this->setMissionActivity($user, $mission, new \DateTimeImmutable('-30 seconds'));

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/missions/'.$mission->getId().'/skip');
        self::assertSame(200, $client->getResponse()->getStatusCode(), $client->getResponse()->getContent());
        $envelope = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame(2, $envelope['data']['diamondsSpent']);

        $em = $this->entityManager();
        $em->refresh($user);
        self::assertSame(8, $user->getDiamonds());
        self::assertNotNull($user->getCurrentActivity());
    }

    public function testSkipMissionFailsWithoutDiamonds(): void
    {
        $user = $this->makePersistedActivatedUser();
        $user->setDiamonds(0);
        $this->entityManager()->flush();

        $mission = $this->makeMissionForUser($user, [
            'durationInSeconds' => 3600,
            'energyCost' => 1,
        ]);
        $this->setMissionActivity($user, $mission, new \DateTimeImmutable('now'));

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/missions/'.$mission->getId().'/skip');
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('notEnoughDiamonds', $problem['detail']);
    }
}
