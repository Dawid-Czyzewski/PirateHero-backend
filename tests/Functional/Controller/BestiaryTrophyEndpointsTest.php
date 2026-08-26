<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Bestiary\BestiaryTrophyCatalog;
use App\Entity\PlayerTitle;
use App\Entity\User;
use App\Entity\UserBestiaryEntry;
use App\Entity\UserBestiaryTrophy;
use App\Enum\TitleUnlockType;
use App\Service\Progression\TitleCodes;
use App\Tests\Functional\ApiWebTestCase;

final class BestiaryTrophyEndpointsTest extends ApiWebTestCase
{
    public function testGetTrophiesReturnsEnvelope(): void
    {
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/bestiary/trophies');

        self::assertResponseIsSuccessful();
        $data = $this->assertJsonEnvelopeData($client->getResponse());
        self::assertSame(0, $data['discoveredCount']);
        self::assertSame(50, $data['total']);
        self::assertCount(4, $data['trophies']);
        self::assertSame(0, $data['unclaimedCount']);
    }

    public function testClaimUnlockedTrophyGrantsRewards(): void
    {
        $this->ensureArchivistTitle();
        $user = $this->makePersistedActivatedUser();
        $em = $this->entityManager();
        $goldBefore = $user->getGold();
        $level = (int) ($user->getLevel()?->getName() ?? '1');

        for ($i = 1; $i <= 13; ++$i) {
            $entry = new UserBestiaryEntry();
            $entry->setUser($user);
            $entry->setDungeonId('krypta');
            $entry->setStage($i <= 10 ? $i : 1);
            if ($i > 10) {
                $entry->setDungeonId('kraken');
                $entry->setStage($i - 10);
            }
            $entry->setDefeatedAt(new \DateTimeImmutable());
            $em->persist($entry);
        }
        $em->flush();

        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/users/bestiary/trophies');
        self::assertResponseIsSuccessful();
        $status = $this->assertJsonEnvelopeData($client->getResponse());
        self::assertSame(13, $status['discoveredCount']);
        self::assertGreaterThanOrEqual(1, $status['unclaimedCount']);

        $def = BestiaryTrophyCatalog::find('bestiary_trophy_25');
        self::assertNotNull($def);
        $expected = BestiaryTrophyCatalog::claimReward($def, $level);

        $client->request('POST', '/api/users/bestiary/trophies/bestiary_trophy_25/claim');
        self::assertResponseIsSuccessful();
        $decoded = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertSame('bestiaryTrophyClaimed', $decoded['meta']['message'] ?? null);
        self::assertSame($expected['gold'], $decoded['data']['rewards']['gold'] ?? null);
        self::assertSame($goldBefore + $expected['gold'], $decoded['data']['updatedUser']['gold'] ?? null);

        $em->clear();
        $row = $em->getRepository(UserBestiaryTrophy::class)->findOneBy([
            'user' => $em->find(User::class, $user->getId()),
            'trophyCode' => 'bestiary_trophy_25',
        ]);
        self::assertNotNull($row);
        self::assertTrue($row->isRewardClaimed());
    }

    public function testClaimLockedTrophyReturnsError(): void
    {
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('POST', '/api/users/bestiary/trophies/bestiary_trophy_25/claim');
        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertSame('bestiaryTrophyNotUnlocked', $problem['detail']);
    }

    private function ensureArchivistTitle(): void
    {
        $em = $this->entityManager();
        $repo = $em->getRepository(PlayerTitle::class);
        if ($repo->findOneBy(['code' => TitleCodes::BESTIARY_ARCHIVIST]) !== null) {
            return;
        }

        $title = new PlayerTitle();
        $title->setCode(TitleCodes::BESTIARY_ARCHIVIST);
        $title->setNameKey('titles.bestiary_archivist.name');
        $title->setDescriptionKey('titles.bestiary_archivist.unlockHint');
        $title->setUnlockType(TitleUnlockType::MANUAL);
        $title->setSortOrder(204);
        $em->persist($title);
        $em->flush();
    }
}
