<?php

declare(strict_types=1);

namespace App\Tests\Unit\Combat\Domain;

use App\Character\Domain\Model\Appearance;
use App\Combat\Application\Simulation\FightSimulator;
use App\Combat\Domain\Catalog\NpcCatalog;
use App\Combat\Domain\Exception\NpcNotFound;
use App\Combat\Domain\Model\Fighter;
use App\Combat\Domain\Model\Npc;
use App\Combat\Domain\Service\FightEngine;
use App\Tests\Double\Combat\SeededRandomIntProvider;
use PHPUnit\Framework\TestCase;

final class NpcCatalogTest extends TestCase
{
    private const SEED = 20261122;
    private const FIGHTS = 300;

    public function testHasEightNpcsWithUniqueSlugsAndNicks(): void
    {
        $npcs = new NpcCatalog()->all();

        self::assertCount(8, $npcs);
        self::assertCount(8, array_unique(array_map(static fn (Npc $npc): string => $npc->id, $npcs)));
        self::assertCount(8, array_unique(array_map(static fn (Npc $npc): string => $npc->nick(), $npcs)));
        foreach ($npcs as $npc) {
            self::assertMatchesRegularExpression('/^[a-z]+(-[a-z]+)*$/', $npc->id);
        }
    }

    public function testCoversLevelsOneToEightInOrder(): void
    {
        self::assertSame(range(1, 8), array_map(static fn (Npc $npc): int => $npc->level(), new NpcCatalog()->all()));
    }

    public function testLooksUseTheCharacterAppearanceVariants(): void
    {
        foreach (new NpcCatalog()->all() as $npc) {
            $look = $npc->look;
            $appearance = new Appearance($look->face, $look->hair, $look->beard, $look->skin, $look->tattoo);

            self::assertSame($look->face, $appearance->face(), $npc->id);
        }
    }

    public function testStrongerNpcsHaveMoreStatPoints(): void
    {
        $points = array_map(
            static fn (Npc $npc): int => $npc->fighter->strength() + $npc->fighter->endurance() + $npc->fighter->technique() + $npc->fighter->charisma(),
            new NpcCatalog()->all(),
        );

        $sorted = $points;
        sort($sorted);
        self::assertSame($sorted, $points);
        self::assertSame(4, $points[0]);
    }

    public function testFindsNpcBySlug(): void
    {
        self::assertSame('Rekin', new NpcCatalog()->ofId('rekin')->nick());
    }

    public function testRejectsUnknownSlug(): void
    {
        $this->expectException(NpcNotFound::class);

        new NpcCatalog()->ofId('ktos-nieznany');
    }

    public function testFreshPlayerBeatsOnlyTheFirstNpc(): void
    {
        $simulator = new FightSimulator(new FightEngine(new SeededRandomIntProvider(self::SEED)));
        $fresh = new Fighter('Gracz', 1, 1, 1, 1, 1);

        $rates = array_map(
            static fn (Npc $npc): float => $simulator->winRate($fresh, $npc->fighter, self::FIGHTS),
            new NpcCatalog()->all(),
        );

        self::assertGreaterThan(0.6, $rates[0]);
        foreach (\array_slice($rates, 1) as $rate) {
            self::assertLessThan(0.05, $rate);
        }
    }
}
