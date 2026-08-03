<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Level;
use App\Entity\UserDungeonProgress;
use App\Tests\Functional\ApiWebTestCase;

final class DungeonTest extends ApiWebTestCase
{
    public function testGetProgressReturnsEnvelope(): void
    {
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/dungeons/progress');
        $this->assertJsonEnvelopeSuccess($client->getResponse());
        $body = json_decode($client->getResponse()->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('progress', $body['data']);
        self::assertArrayHasKey('playerStats', $body['data']);
    }

    public function testFightStageReturnsBattleAndSavesProgressOnWin(): void
    {
        $user = $this->makePersistedActivatedUser();
        $this->setUserLevel($user, '80');

        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/users/dungeons/fight',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['dungeonId' => 'krypta', 'stage' => 1], \JSON_THROW_ON_ERROR),
        );

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $body = json_decode($client->getResponse()->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('won', $body['data']);
        self::assertArrayHasKey('logs', $body['data']);
        self::assertArrayHasKey('opponent', $body['data']);
        if ($body['data']['won'] === true) {
            self::assertSame(1, $body['data']['progress']['krypta'] ?? 0);
            self::assertArrayHasKey('rewards', $body['data']);
            self::assertSame(40, $body['data']['rewards']['gold']);
            self::assertSame(8, $body['data']['rewards']['exp']);
            self::assertArrayHasKey('updatedUser', $body['data']);
            self::assertNotNull($body['data']['updatedUser']);
        }
    }

    public function testFightLossReturnsZeroRewards(): void
    {
        $user = $this->makePersistedActivatedUser();
        $this->setUserLevel($user, '80');
        $em = $this->entityManager();
        $user->setGold(500);
        $user->setExperiencePoints(10);
        $stats = $user->getUserBaseStatistics();
        if ($stats !== null) {
            $stats->setStrength(1);
            $stats->setAgility(1);
            $stats->setEndurance(1);
            $stats->setIntelligence(1);
            $stats->setLuck(1);
        }
        $em->flush();

        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/users/dungeons/fight',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['dungeonId' => 'krypta', 'stage' => 1], \JSON_THROW_ON_ERROR),
        );

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $body = json_decode($client->getResponse()->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);
        if ($body['data']['won'] === false) {
            self::assertSame(['gold' => 0, 'exp' => 0], $body['data']['rewards']);
            self::assertNull($body['data']['updatedUser']);
            self::assertSame(500, $user->getGold());
        } else {
            self::markTestSkipped('Fight was won; cannot assert loss rewards on this run.');
        }
    }

    public function testCannotFightClearedStage(): void
    {
        $user = $this->makePersistedActivatedUser();
        $this->setUserLevel($user, '80');

        $progress = new UserDungeonProgress();
        $progress->setUser($user);
        $progress->setDungeonId('krypta');
        $progress->setClearedStage(1);
        $em = $this->entityManager();
        $em->persist($progress);
        $em->flush();

        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/users/dungeons/fight',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['dungeonId' => 'krypta', 'stage' => 1], \JSON_THROW_ON_ERROR),
        );
        self::assertSame(400, $client->getResponse()->getStatusCode());
    }

    public function testCannotFightDuringMission(): void
    {
        $user = $this->makePersistedActivatedUser();
        $this->setUserLevel($user, '80');
        $mission = $this->makeMissionForUser($user);
        $this->setMissionActivity($user, $mission, new \DateTime());

        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/users/dungeons/fight',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['dungeonId' => 'krypta', 'stage' => 1], \JSON_THROW_ON_ERROR),
        );

        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('finishMissionFirst', $problem['detail']);
    }

    public function testCannotAccessProgressDuringMission(): void
    {
        $user = $this->makePersistedActivatedUser();
        $mission = $this->makeMissionForUser($user);
        $this->setMissionActivity($user, $mission, new \DateTime());

        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/dungeons/progress');

        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('finishMissionFirst', $problem['detail']);
    }

    private function setUserLevel(\App\Entity\User $user, string $levelName): void
    {
        $em = $this->entityManager();
        $level = $em->getRepository(Level::class)->findOneBy(['name' => $levelName]);
        if ($level === null) {
            $level = new Level();
            $level->setName($levelName);
            $level->setExpToNextLevel(1000);
            $em->persist($level);
        }
        $user->setLevel($level);
        $em->flush();
    }
}
