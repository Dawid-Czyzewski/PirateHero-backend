<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Domain\Constants\WeeklyArenaFameConstants;
use App\Entity\PlayerTitle;
use App\Entity\UserWeeklyArenaFame;
use App\Enum\TitleUnlockType;
use App\Service\Progression\TitleCodes;
use App\Tests\Functional\ApiWebTestCase;

final class WeeklyArenaFameEndpointsTest extends ApiWebTestCase
{
    public function testGetStatusCreatesRowEnvelope(): void
    {
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/weekly-arena-fame/status');

        self::assertResponseIsSuccessful();
        $data = $this->assertJsonEnvelopeData($client->getResponse());
        self::assertSame(0, $data['fameEarned']);
        self::assertSame(0, $data['unclaimedCount']);
        self::assertCount(3, $data['tiers']);
    }

    public function testClaimWithoutProgressReturnsBusinessError(): void
    {
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/weekly-arena-fame/status');
        self::assertResponseIsSuccessful();

        $client->request(
            'POST',
            '/api/users/weekly-arena-fame/claim',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['tier' => 1], JSON_THROW_ON_ERROR),
        );
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('weeklyArenaFameTierNotReached', $problem['detail']);
    }

    public function testClaimTierOneGrantsRewards(): void
    {
        $this->ensureArenaFameTitles();
        $user = $this->makePersistedActivatedUser();
        $em = $this->entityManager();
        $goldBefore = $user->getGold();
        $level = (int) ($user->getLevel()?->getName() ?? '1');
        $expected = WeeklyArenaFameConstants::claimReward(1, $level);

        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/weekly-arena-fame/status');
        self::assertResponseIsSuccessful();

        $row = $em->getRepository(UserWeeklyArenaFame::class)->findOneBy(['user' => $user]);
        self::assertInstanceOf(UserWeeklyArenaFame::class, $row);
        $row->setFameEarned(90);
        $em->persist($row);
        $em->flush();

        $client->request(
            'POST',
            '/api/users/weekly-arena-fame/claim',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(['tier' => 1], JSON_THROW_ON_ERROR),
        );
        self::assertResponseIsSuccessful();
        $decoded = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame('weeklyArenaFameClaimed', $decoded['meta']['message'] ?? null);
        $data = $decoded['data'];
        self::assertSame($expected['gold'], $data['rewards']['gold'] ?? null);
        self::assertTrue($data['titleGranted'] ?? false);
        self::assertSame(TitleCodes::WEEKLY_ARENA_FIGHTER, $data['titleCode'] ?? null);
        self::assertSame($goldBefore + $expected['gold'], $data['updatedUser']['gold'] ?? null);
        self::assertTrue($data['status']['tiers'][0]['claimed'] ?? false);
    }

    private function ensureArenaFameTitles(): void
    {
        $em = $this->entityManager();
        foreach ([
            [TitleCodes::WEEKLY_ARENA_FIGHTER, 201],
            [TitleCodes::WEEKLY_ARENA_CHAMPION, 202],
            [TitleCodes::WEEKLY_ARENA_LEGEND, 203],
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
