<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Tests\Functional\ApiWebTestCase;

final class TrainingFlowEndpointsTest extends ApiWebTestCase
{
    public function testStartTrainingSuccessConsumesPoints(): void
    {
        $user = $this->makePersistedActivatedUser();
        $user->setTrainingPoints(25);
        $this->entityManager()->flush();
        $training = $this->makeTrainingForUser($user, ['trainingPointsCost' => 4]);

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/trainings/'.$training->getId().'/start');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $envelope = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertTrue($envelope['data']['started']);
    }

    public function testStartTrainingFailsWhenNotEnoughPoints(): void
    {
        $user = $this->makePersistedActivatedUser();
        $user->setTrainingPoints(0);
        $this->entityManager()->flush();
        $training = $this->makeTrainingForUser($user, ['trainingPointsCost' => 2]);

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/trainings/'.$training->getId().'/start');
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('notEnoughTrainingPoints', $problem['detail']);
    }

    public function testCancelTrainingSuccess(): void
    {
        $user = $this->makePersistedActivatedUser();
        $training = $this->makeTrainingForUser($user, ['trainingPointsCost' => 3]);
        $this->setTrainingActivity($user, $training, new \DateTimeImmutable('-5 minutes'));

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/trainings/'.$training->getId().'/cancel');
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $envelope = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertTrue($envelope['data']['cancelled']);
    }

    public function testCompleteTrainingTooEarlyFails(): void
    {
        $user = $this->makePersistedActivatedUser();
        $training = $this->makeTrainingForUser($user, ['durationInSeconds' => 9999]);
        $this->setTrainingActivity($user, $training, new \DateTimeImmutable('now'));

        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/trainings/'.$training->getId().'/complete');
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('trainingNotComplete', $problem['detail']);
    }

    public function testTrainingOwnershipForbidden(): void
    {
        $owner = $this->makePersistedActivatedUser();
        $other = $this->makePersistedActivatedUser();
        $training = $this->makeTrainingForUser($owner);

        $client = $this->createAuthenticatedClient($other);
        $client->request('POST', '/api/trainings/'.$training->getId().'/start');
        self::assertSame(403, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('trainingOwnershipRequired', $problem['detail']);
    }

    public function testTrainingNotFoundForAllActions(): void
    {
        $client = $this->createAuthenticatedClient();
        foreach (['start', 'cancel', 'complete'] as $action) {
            $client->request('POST', '/api/trainings/999999/'.$action);
            self::assertSame(404, $client->getResponse()->getStatusCode());
            $problem = $this->assertProblemJson($client->getResponse());
            self::assertSame('trainingNotFound', $problem['detail']);
        }
    }
}
