<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\PlayerTitle;
use App\Enum\TitleUnlockType;
use App\Progression\LevelRankTitleCatalog;
use App\Service\Progression\TitleService;
use App\Tests\Functional\ApiWebTestCase;

final class TitleControllerTest extends ApiWebTestCase
{
    public function testGetUserTitlesReturnsEnvelopeWithCatalog(): void
    {
        $this->ensurePlayerTitlesSeeded();
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request('GET', '/api/user_titles');

        $decoded = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertArrayHasKey('titles', $decoded['data']);
        self::assertGreaterThanOrEqual(54, \count($decoded['data']['titles']));
        $codes = array_column($decoded['data']['titles'], 'code');
        self::assertContains('rookie', $codes);
        $rookie = null;
        foreach ($decoded['data']['titles'] as $row) {
            if (($row['code'] ?? '') === 'rookie') {
                $rookie = $row;
                break;
            }
        }
        self::assertNotNull($rookie);
        self::assertTrue($rookie['unlocked']);
    }

    public function testEquipUnlockedTitleSucceeds(): void
    {
        $this->ensurePlayerTitlesSeeded();
        $user = $this->makePersistedActivatedUser();
        static::getContainer()->get(TitleService::class)->syncUnlocks($user);

        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/user_titles/equip',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['titleCode' => 'rookie'], \JSON_THROW_ON_ERROR)
        );

        $decoded = $this->assertJsonEnvelopeSuccess($client->getResponse());
        self::assertTrue($decoded['data']['equipped']);
        self::assertSame('rookie', $decoded['data']['equippedTitleCode']);
    }

    public function testEquipLockedTitleReturnsProblemJson(): void
    {
        $this->ensurePlayerTitlesSeeded();
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/user_titles/equip',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['titleCode' => 'veteran'], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertStringContainsString('titleNotUnlocked', (string) $problem['detail']);
    }

    public function testEquipUnknownTitleReturnsProblemJson(): void
    {
        $this->ensurePlayerTitlesSeeded();
        $user = $this->makePersistedActivatedUser();
        $client = $this->createAuthenticatedClient($user);
        $client->request(
            'POST',
            '/api/user_titles/equip',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['titleCode' => 'unknown_title'], \JSON_THROW_ON_ERROR)
        );

        self::assertSame(400, $client->getResponse()->getStatusCode());
        $problem = $this->assertProblemJson($client->getResponse());
        self::assertStringContainsString('titleNotFound', (string) $problem['detail']);
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
            ['collector', 'titles.collector.name', 'titles.collector.unlockHint', TitleUnlockType::ITEMS_COLLECTED, 10, null, 9],
            ['treasure_hunter', 'titles.treasure_hunter.name', 'titles.treasure_hunter.unlockHint', TitleUnlockType::ITEMS_COLLECTED, 75, null, 10],
            ['dungeon_master', 'titles.dungeon_master.name', 'titles.dungeon_master.unlockHint', TitleUnlockType::ALL_DUNGEONS_COMPLETED, null, null, 11],
            ['veteran_collector', 'titles.veteran_collector.name', 'titles.veteran_collector.unlockHint', TitleUnlockType::RARE_EQUIPMENT_FULL, null, null, 12],
            ['legendary_collector', 'titles.legendary_collector.name', 'titles.legendary_collector.unlockHint', TitleUnlockType::ITEMS_COLLECTED, 100, null, 13],
            ['sea_legend', 'titles.sea_legend.name', 'titles.sea_legend.unlockHint', TitleUnlockType::ALL_DUNGEONS_AND_LEVEL, 50, null, 14],
            ['master_collector', 'titles.master_collector.name', 'titles.master_collector.unlockHint', TitleUnlockType::ITEMS_COLLECTED, 85, null, 15],
            ['legendary_hunter', 'titles.legendary_hunter.name', 'titles.legendary_hunter.unlockHint', TitleUnlockType::LEGENDARY_ITEMS_COLLECTED, 5, null, 16],
            ['veteran_captain', 'titles.veteran_captain.name', 'titles.veteran_captain.unlockHint', TitleUnlockType::LEVEL_REACHED, 35, null, 17],
            ['undead_slayer', 'titles.undead_slayer.name', 'titles.undead_slayer.unlockHint', TitleUnlockType::DUNGEON_COMPLETED, null, 'krypta', 18],
            ['beast_hunter', 'titles.beast_hunter.name', 'titles.beast_hunter.unlockHint', TitleUnlockType::BESTIARY_COMPLETE, 50, null, 19],
            ['black_corsair', 'titles.black_corsair.name', 'titles.black_corsair.unlockHint', TitleUnlockType::FIGHTS_WON, 250, null, 20],
            ['elite_captain', 'titles.elite_captain.name', 'titles.elite_captain.unlockHint', TitleUnlockType::LEVEL_REACHED, 50, null, 21],
            ['fortress_lord', 'titles.fortress_lord.name', 'titles.fortress_lord.unlockHint', TitleUnlockType::DUNGEON_COMPLETED, null, 'forteca', 22],
            ['atlantis_guardian', 'titles.atlantis_guardian.name', 'titles.atlantis_guardian.unlockHint', TitleUnlockType::DUNGEON_COMPLETED, null, 'palac', 23],
            ['legend_collector', 'titles.legend_collector.name', 'titles.legend_collector.unlockHint', TitleUnlockType::LEGENDARY_ITEMS_COLLECTED, 15, null, 24],
            ['ocean_master', 'titles.ocean_master.name', 'titles.ocean_master.unlockHint', TitleUnlockType::FIGHTS_WON, 1000, null, 25],
            ['undefeated_pirate', 'titles.undefeated_pirate.name', 'titles.undefeated_pirate.unlockHint', TitleUnlockType::FIGHTS_WON, 500, null, 26],
            ['great_explorer', 'titles.great_explorer.name', 'titles.great_explorer.unlockHint', TitleUnlockType::ALL_DUNGEONS_COMPLETED, null, null, 27],
            ['fight_veteran_50', 'titles.fight_veteran_50.name', 'titles.fight_veteran_50.unlockHint', TitleUnlockType::FIGHTS_WON, 50, null, 28],
            ['fight_veteran_100', 'titles.fight_veteran_100.name', 'titles.fight_veteran_100.unlockHint', TitleUnlockType::FIGHTS_WON, 100, null, 29],
            ['epic_collector', 'titles.epic_collector.name', 'titles.epic_collector.unlockHint', TitleUnlockType::EPIC_ITEMS_COLLECTED, 10, null, 30],
            ['epic_lord', 'titles.epic_lord.name', 'titles.epic_lord.unlockHint', TitleUnlockType::EPIC_EQUIPMENT_FULL, null, null, 31],
            ['legendary_lord', 'titles.legendary_lord.name', 'titles.legendary_lord.unlockHint', TitleUnlockType::LEGENDARY_EQUIPMENT_FULL, null, null, 32],
            ['grand_collector', 'titles.grand_collector.name', 'titles.grand_collector.unlockHint', TitleUnlockType::ITEMS_COLLECTED, 200, null, 33],
            ['legend_slayer', 'titles.legend_slayer.name', 'titles.legend_slayer.unlockHint', TitleUnlockType::LEGENDARY_ITEMS_COLLECTED, 25, null, 34],
            ['ocean_ruler', 'titles.ocean_ruler.name', 'titles.ocean_ruler.unlockHint', TitleUnlockType::LEVEL_REACHED, 150, null, 35],
            ['atlantis_master', 'titles.atlantis_master.name', 'titles.atlantis_master.unlockHint', TitleUnlockType::DUNGEON_COMPLETED, null, 'palac', 36],
            ['golden_corsair', 'titles.golden_corsair.name', 'titles.golden_corsair.unlockHint', TitleUnlockType::GOLD_BALANCE, 50000, null, 37],
            ['expedition_veteran', 'titles.expedition_veteran.name', 'titles.expedition_veteran.unlockHint', TitleUnlockType::FIGHTS_WON, 2000, null, 38],
            ['titan_slayer', 'titles.titan_slayer.name', 'titles.titan_slayer.unlockHint', TitleUnlockType::ALL_DUNGEONS_AND_LEVEL, 100, null, 39],
            ['relic_lord', 'titles.relic_lord.name', 'titles.relic_lord.unlockHint', TitleUnlockType::ITEMS_COLLECTED, 300, null, 40],
            ['great_discoverer', 'titles.great_discoverer.name', 'titles.great_discoverer.unlockHint', TitleUnlockType::BESTIARY_COMPLETE, 50, null, 41],
            ['immortal_captain', 'titles.immortal_captain.name', 'titles.immortal_captain.unlockHint', TitleUnlockType::LEVEL_REACHED, 200, null, 42],
            ['fight_archmaster', 'titles.fight_archmaster.name', 'titles.fight_archmaster.unlockHint', TitleUnlockType::FIGHTS_WON, 10000, null, 43],
            ['atlantis_emperor', 'titles.atlantis_emperor.name', 'titles.atlantis_emperor.unlockHint', TitleUnlockType::LEVEL_REACHED, 250, null, 44],
            ['storm_lord', 'titles.storm_lord.name', 'titles.storm_lord.unlockHint', TitleUnlockType::FIGHTS_WON, 7500, null, 45],
            ['leviathan_hunter', 'titles.leviathan_hunter.name', 'titles.leviathan_hunter.unlockHint', TitleUnlockType::LEGENDARY_ITEMS_COLLECTED, 50, null, 46],
            ['collection_master', 'titles.collection_master.name', 'titles.collection_master.unlockHint', TitleUnlockType::ITEMS_COLLECTED, 400, null, 47],
            ['grand_seeker', 'titles.grand_seeker.name', 'titles.grand_seeker.unlockHint', TitleUnlockType::ITEMS_COLLECTED, 500, null, 48],
            ['ocean_slayer', 'titles.ocean_slayer.name', 'titles.ocean_slayer.unlockHint', TitleUnlockType::FIGHTS_WON, 15000, null, 49],
            ['relic_sovereign', 'titles.relic_sovereign.name', 'titles.relic_sovereign.unlockHint', TitleUnlockType::EPIC_ITEMS_COLLECTED, 75, null, 50],
            ['unbeaten_corsair', 'titles.unbeaten_corsair.name', 'titles.unbeaten_corsair.unlockHint', TitleUnlockType::LEVEL_REACHED, 225, null, 51],
            ['legend_lord', 'titles.legend_lord.name', 'titles.legend_lord.unlockHint', TitleUnlockType::LEGENDARY_ITEMS_COLLECTED, 75, null, 52],
            ['greatest_explorer', 'titles.greatest_explorer.name', 'titles.greatest_explorer.unlockHint', TitleUnlockType::ITEMS_COLLECTED, 350, null, 53],
            ['eternal_captain', 'titles.eternal_captain.name', 'titles.eternal_captain.unlockHint', TitleUnlockType::LEVEL_REACHED, 300, null, 54],
        ];

        foreach (LevelRankTitleCatalog::definitions() as $def) {
            $definitions[] = [
                $def['code'],
                $def['nameKey'],
                $def['descriptionKey'],
                TitleUnlockType::LEVEL_REACHED,
                $def['level'],
                null,
                $def['sortOrder'],
            ];
        }

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
}
