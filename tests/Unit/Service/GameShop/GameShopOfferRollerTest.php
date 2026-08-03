<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\GameShop;

use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Repository\WearableItemTemplateRepository;
use App\Service\GameShop\GameShopOfferRoller;
use App\Service\GameShop\WearableVariantResolver;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class GameShopOfferRollerTest extends TestCase
{
    private function createRoller(?WearableItemTemplateRepository $repository = null): GameShopOfferRoller
    {
        $repository ??= $this->createMock(WearableItemTemplateRepository::class);
        $repository->method('findAvailableForTypeAndLevel')->willReturn([]);

        $resolver = new WearableVariantResolver($repository, new NullLogger());

        return new GameShopOfferRoller($resolver);
    }

    public function testRollReturnsValidShape(): void
    {
        $roller = $this->createRoller();
        $row = $roller->roll(WearableItemType::Helmet, 5);

        self::assertArrayHasKey('nameKey', $row);
        self::assertArrayHasKey('imageKey', $row);
        self::assertArrayHasKey('price', $row);
        self::assertArrayHasKey('rarity', $row);
        self::assertArrayHasKey('shopStats', $row);
        self::assertNotEmpty($row['shopStats']);
        self::assertGreaterThan(0, $row['price']);
    }

    public function testRollVariesOffersAcrossCalls(): void
    {
        $roller = $this->createRoller();
        $keys = [];
        for ($i = 0; $i < 50; ++$i) {
            $keys[$roller->roll(WearableItemType::Helmet, 1)['nameKey']] = true;
        }

        self::assertGreaterThan(1, \count($keys), 'Expected multiple helmet variants over rolls');
    }

    public function testHigherLevelYieldsHigherTypicalPrice(): void
    {
        $roller = $this->createRoller();
        $sumLow = 0;
        $sumHigh = 0;
        for ($i = 0; $i < 40; ++$i) {
            $sumLow += $roller->roll(WearableItemType::Boots, 2)['price'];
            $sumHigh += $roller->roll(WearableItemType::Boots, 90)['price'];
        }

        self::assertGreaterThan($sumLow, $sumHigh);
    }

    public function testHighLevelFallsBackToMaxTemplatePoolWithoutException(): void
    {
        $roller = $this->createRoller();
        $row = $roller->roll(WearableItemType::Helmet, 15);

        self::assertArrayHasKey('nameKey', $row);
        self::assertArrayHasKey('price', $row);
        self::assertGreaterThan(0, $row['price']);
    }

    public function testLevel25FallsBackWithoutException(): void
    {
        $roller = $this->createRoller();
        $row = $roller->roll(WearableItemType::Weapon, 25);

        self::assertArrayHasKey('nameKey', $row);
        self::assertArrayHasKey('price', $row);
        self::assertGreaterThan(0, $row['price']);
    }

    public function testHigherLevelYieldsHigherTypicalStatSum(): void
    {
        $roller = $this->createRoller();
        $sumLow = 0;
        $sumHigh = 0;
        for ($i = 0; $i < 40; ++$i) {
            $sumLow += $this->sumStatValues($roller->roll(WearableItemType::Weapon, 10)['shopStats']);
            $sumHigh += $this->sumStatValues($roller->roll(WearableItemType::Weapon, 15)['shopStats']);
        }

        self::assertGreaterThan($sumLow, $sumHigh);
    }

    public function testLevel25TypicalStatSumGreaterThanLevel15(): void
    {
        $roller = $this->createRoller();
        $sumLow = 0;
        $sumHigh = 0;
        for ($i = 0; $i < 40; ++$i) {
            $sumLow += $this->sumStatValues($roller->roll(WearableItemType::Weapon, 15)['shopStats']);
            $sumHigh += $this->sumStatValues($roller->roll(WearableItemType::Weapon, 25)['shopStats']);
        }

        self::assertGreaterThan($sumLow, $sumHigh);
    }

    public function testLevel25TypicalPriceGreaterThanLevel15(): void
    {
        $roller = $this->createRoller();
        $sumLow = 0;
        $sumHigh = 0;
        for ($i = 0; $i < 40; ++$i) {
            $sumLow += $roller->roll(WearableItemType::Armor, 15)['price'];
            $sumHigh += $roller->roll(WearableItemType::Armor, 25)['price'];
        }

        self::assertGreaterThan($sumLow, $sumHigh);
    }

    public function testLevel35FallsBackWithoutException(): void
    {
        $roller = $this->createRoller();
        $row = $roller->roll(WearableItemType::Weapon, 35);

        self::assertArrayHasKey('nameKey', $row);
        self::assertArrayHasKey('price', $row);
        self::assertGreaterThan(0, $row['price']);
    }

    public function testLevel30TypicalStatSumGreaterThanLevel25(): void
    {
        $roller = $this->createRoller();
        $sumLow = 0;
        $sumHigh = 0;

        for ($i = 0; $i < 200; ++$i) {
            $sumLow += $this->sumStatValues($roller->roll(WearableItemType::Weapon, 25)['shopStats']);
            $sumHigh += $this->sumStatValues($roller->roll(WearableItemType::Weapon, 30)['shopStats']);
        }

        self::assertGreaterThan($sumLow, $sumHigh);
    }

    public function testLevel35TypicalPriceGreaterThanLevel30(): void
    {
        $roller = $this->createRoller();
        $sumLow = 0;
        $sumHigh = 0;
        for ($i = 0; $i < 200; ++$i) {
            $sumLow += $roller->roll(WearableItemType::Armor, 30)['price'];
            $sumHigh += $roller->roll(WearableItemType::Armor, 35)['price'];
        }

        self::assertGreaterThan($sumLow, $sumHigh);
    }

    public function testRareLevel15StatsBelowEpicLevel20(): void
    {
        $roller = $this->createRoller();
        $rareSum = $this->averageStatSumForRarityAtLevel($roller, WearableItemType::Weapon, 15, WearableItemRarity::RARE);
        $epicSum = $this->averageStatSumForRarityAtLevel($roller, WearableItemType::Weapon, 20, WearableItemRarity::EPIC);

        self::assertGreaterThan($rareSum, $epicSum);
    }

    public function testEpicLevel20StatsBelowLegendaryLevel25(): void
    {
        $roller = $this->createRoller();
        $epicSum = $this->averageStatSumForRarityAtLevel($roller, WearableItemType::Weapon, 20, WearableItemRarity::EPIC);
        $legendarySum = $this->averageStatSumForRarityAtLevel($roller, WearableItemType::Weapon, 25, WearableItemRarity::LEGENDARY);

        self::assertGreaterThan($epicSum, $legendarySum);
    }

    public function testLevelTenRareStatsStayInExpectedBand(): void
    {
        $roller = $this->createRoller();
        $statValues = [];

        for ($i = 0; $i < 120; ++$i) {
            $row = $roller->roll(WearableItemType::Weapon, 10);
            foreach ($row['shopStats'] as $line) {
                $statValues[] = $line['value'];
            }
        }

        self::assertNotEmpty($statValues);
        self::assertGreaterThanOrEqual(4, min($statValues));
        self::assertLessThanOrEqual(35, max($statValues));
    }

    private function averageStatSumForRarityAtLevel(
        GameShopOfferRoller $roller,
        WearableItemType $type,
        int $level,
        WearableItemRarity $rarity,
    ): float {
        $sum = 0.0;
        $count = 0;
        for ($i = 0; $i < 80; ++$i) {
            $row = $roller->roll($type, $level);
            if ($row['rarity'] !== $rarity) {
                continue;
            }
            $sum += $this->sumStatValues($row['shopStats']);
            ++$count;
        }

        self::assertGreaterThan(0, $count, sprintf('Expected at least one %s roll at level %d', $rarity->value, $level));

        return $sum / $count;
    }

    /**
     * @param list<array{statId: string, value: int}> $stats
     */
    private function sumStatValues(array $stats): int
    {
        return array_sum(array_map(static fn (array $line): int => $line['value'], $stats));
    }
}
