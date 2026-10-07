<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\PlayerTitle;
use App\Entity\UserWeekendTournament;
use App\Enum\TitleUnlockType;
use App\Service\Progression\TitleCodes;
use App\Tests\Functional\ApiWebTestCase;

final class WeekendTournamentEndpointsTest extends ApiWebTestCase
{
    public function testGetStatusCreatesRowEnvelope(): void
    {
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/weekend-tournament/status');

        self::assertResponseIsSuccessful();
        $data = $this->assertJsonEnvelopeData($client->getResponse());
        self::assertArrayHasKey('active', $data);
        self::assertArrayHasKey('points', $data);
        self::assertSame(0, $data['points']);
        self::assertArrayHasKey('leaderboard', $data);
        self::assertArrayHasKey('unclaimedCount', $data);
    }

    public function testGetLeaderboardEnvelope(): void
    {
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/weekend-tournament/leaderboard');

        self::assertResponseIsSuccessful();
        $data = $this->assertJsonEnvelopeData($client->getResponse());
        self::assertArrayHasKey('entries', $data);
        self::assertIsArray($data['entries']);
    }

    public function testClaimWithoutPointsReturnsBusinessError(): void
    {
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/weekend-tournament/status');
        self::assertResponseIsSuccessful();

        $client->request('POST', '/api/users/weekend-tournament/claim');
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertContains($problem['detail'], [
            'weekendTournamentNotEnoughPoints',
            'weekendTournamentStillActive',
        ]);
    }

    public function testClaimWithPointsGrantsRewardsWhenWeekendClosed(): void
    {
        $dow = (int) (new \DateTimeImmutable('now'))->format('N');
        if ($dow === 6 || $dow === 7) {
            self::markTestSkipped('Cannot claim while weekend tournament is active');
        }

        $this->ensureWeekendTitles();
        $user = $this->makePersistedActivatedUser();
        $em = $this->entityManager();
        $goldBefore = $user->getGold();

        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/weekend-tournament/status');
        self::assertResponseIsSuccessful();
        $status = $this->assertJsonEnvelopeData($client->getResponse());

        $row = $em->getRepository(UserWeekendTournament::class)->findOneBy(['user' => $user]);
        self::assertInstanceOf(UserWeekendTournament::class, $row);
        $row->setPoints(5);
        $em->persist($row);
        $em->flush();

        $client->request('POST', '/api/users/weekend-tournament/claim');
        self::assertResponseIsSuccessful();
        $decoded = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame('weekendTournamentClaimed', $decoded['meta']['message'] ?? null);
        $data = $decoded['data'];
        self::assertGreaterThan($goldBefore, $data['updatedUser']['gold'] ?? 0);
        self::assertTrue($data['status']['rewardClaimed'] ?? false);
        self::assertNotNull($status['eventStart'] ?? null);
    }

    private function ensureWeekendTitles(): void
    {
        $em = $this->entityManager();
        foreach ([
            [TitleCodes::WEEKEND_ARENA_CHAMPION, 205],
            [TitleCodes::WEEKEND_GLADIATOR, 206],
        ] as [$code, $sort]) {
            $existing = $em->getRepository(PlayerTitle::class)->findOneBy(['code' => $code]);
            if ($existing instanceof PlayerTitle) {
                continue;
            }
            $title = new PlayerTitle();
            $title->setCode($code);
            $title->setNameKey('titles.'.$code.'.name');
            $title->setDescriptionKey('titles.'.$code.'.unlockHint');
            $title->setUnlockType(TitleUnlockType::MANUAL);
            $title->setSortOrder($sort);
            $em->persist($title);
        }
        $em->flush();
    }
}
