<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Dungeon\DungeonCatalog;
use App\Entity\Level;
use App\Entity\PlayerTitle;
use App\Entity\User;
use App\Entity\UserDungeonProgress;
use App\Enum\DungeonId;
use App\Enum\TitleUnlockType;
use App\Tests\Functional\ApiWebTestCase;

final class RcDungeonMatrixSmokeTest extends ApiWebTestCase
{
    /** @return array<string, array{0: string, 1: int, 2: string}> */
    public static function dungeonProvider(): array
    {
        return [
            'krypta' => ['krypta', 15, 'crypt_hunter'],
            'kraken' => ['kraken', 25, 'kraken_slayer'],
            'forteca' => ['forteca', 40, 'fortress_raider'],
            'wulkan' => ['wulkan', 60, 'volcanic_conqueror'],
            'palac' => ['palac', 80, 'poseidon_champion'],
        ];
    }

    /**
     * @dataProvider dungeonProvider
     */
    public function testDungeonStageOneFightNoServerError(string $dungeonId, int $reqLevel, string $titleSlug): void
    {
        unset($titleSlug);
        $this->ensurePlayerTitlesSeeded();
        $user = $this->makePersistedActivatedUser();
        $this->setUserLevel($user, (string) $reqLevel);

        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/users/dungeons/fight',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['dungeonId' => $dungeonId, 'stage' => 1], \JSON_THROW_ON_ERROR),
        );

        self::assertLessThan(500, $client->getResponse()->getStatusCode());
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $body = json_decode($client->getResponse()->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('won', $body['data']);
        self::assertArrayHasKey('opponent', $body['data']);

        $client->request('GET', '/api/users/bestiary/entries');
        $bestiary = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertGreaterThanOrEqual(50, count($bestiary['data']['entries'] ?? []));

        $client->request('GET', '/api/user_titles');
        $titles = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertGreaterThanOrEqual(8, count($titles['data']['titles'] ?? $titles['data']));
    }

    /**
     * @dataProvider dungeonProvider
     */
    public function testDungeonStageTenCompletionRewardShape(string $dungeonId, int $reqLevel, string $titleSlug): void
    {
        unset($titleSlug);
        $this->ensurePlayerTitlesSeeded();
        $user = $this->makePersistedActivatedUser();
        $this->setUserLevel($user, (string) $reqLevel);
        $this->ensureUserBaseStatistics($user, 500);
        $this->ensureUserStorage($user);

        $progress = new UserDungeonProgress();
        $progress->setUser($user);
        $progress->setDungeonId($dungeonId);
        $progress->setClearedStage(DungeonCatalog::STAGES_PER_DUNGEON - 1);
        $progress->setCompletionRewardClaimed(false);
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
            json_encode(['dungeonId' => $dungeonId, 'stage' => DungeonCatalog::STAGES_PER_DUNGEON], \JSON_THROW_ON_ERROR),
        );

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $body = json_decode($client->getResponse()->getContent() ?: '', true, 512, \JSON_THROW_ON_ERROR);
        self::assertTrue($body['data']['won'] ?? false, "Expected deterministic win on {$dungeonId} stage 10");

        $dungeon = DungeonCatalog::get(DungeonId::from($dungeonId));
        self::assertNotNull($dungeon);
        self::assertSame($dungeon['completionGold'], $body['data']['completionReward']['gold'] ?? null);
        self::assertSame($dungeon['completionDiamonds'], $body['data']['completionReward']['diamonds'] ?? null);
        self::assertTrue($body['data']['dungeonCompleted'] ?? false);
    }

    private function ensurePlayerTitlesSeeded(): void
    {
        static::ensureTestClient();
        $em = $this->entityManager();
        $repo = $em->getRepository(PlayerTitle::class);
        if ($repo->count([]) > 0) {
            return;
        }

        $definitions = [
            ['rookie', 'titles.rookie.name', 'titles.rookie.unlockHint', TitleUnlockType::GAME_START, null, null, 1],
            ['crypt_hunter', 'titles.crypt_hunter.name', 'titles.crypt_hunter.unlockHint', TitleUnlockType::DUNGEON_COMPLETED, null, 'krypta', 2],
            ['kraken_slayer', 'titles.kraken_slayer.name', 'titles.kraken_slayer.unlockHint', TitleUnlockType::DUNGEON_COMPLETED, null, 'kraken', 3],
            ['veteran', 'titles.veteran.name', 'titles.veteran.unlockHint', TitleUnlockType::LEVEL_REACHED, 25, null, 4],
            ['rich_captain', 'titles.rich_captain.name', 'titles.rich_captain.unlockHint', TitleUnlockType::GOLD_BALANCE, 10000, null, 5],
            ['fortress_raider', 'titles.fortress_raider.name', 'titles.fortress_raider.unlockHint', TitleUnlockType::DUNGEON_COMPLETED, null, 'forteca', 6],
            ['volcanic_conqueror', 'titles.volcanic_conqueror.name', 'titles.volcanic_conqueror.unlockHint', TitleUnlockType::DUNGEON_COMPLETED, null, 'wulkan', 7],
            ['poseidon_champion', 'titles.poseidon_champion.name', 'titles.poseidon_champion.unlockHint', TitleUnlockType::DUNGEON_COMPLETED, null, 'palac', 8],
        ];

        foreach ($definitions as [$code, $nameKey, $descriptionKey, $unlockType, $unlockValue, $unlockDungeonId, $sortOrder]) {
            $title = new PlayerTitle();
            $title->setCode($code);
            $title->setNameKey($nameKey);
            $title->setDescriptionKey($descriptionKey);
            $title->setUnlockType($unlockType);
            $title->setUnlockValue($unlockValue);
            $title->setUnlockDungeonId($unlockDungeonId);
            $title->setSortOrder($sortOrder);
            $em->persist($title);
        }

        $em->flush();
    }

    private function setUserLevel(User $user, string $levelName): void
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
