<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Domain\Model;

use App\Character\Domain\Exception\InvalidCharacterData;
use App\Character\Domain\Model\Stats;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StatsTest extends TestCase
{
    public function testInitialStatsAreAllOne(): void
    {
        $stats = Stats::initial();

        self::assertSame(1, $stats->strength());
        self::assertSame(1, $stats->endurance());
        self::assertSame(1, $stats->technique());
        self::assertSame(1, $stats->charisma());
    }

    public function testKeepsEachStat(): void
    {
        $stats = new Stats(strength: 4, endurance: 7, technique: 2, charisma: 9);

        self::assertSame(4, $stats->strength());
        self::assertSame(7, $stats->endurance());
        self::assertSame(2, $stats->technique());
        self::assertSame(9, $stats->charisma());
    }

    /**
     * @return iterable<string, array{array{int, int, int, int}, string}>
     */
    public static function invalidStats(): iterable
    {
        yield 'strength 0' => [[0, 1, 1, 1], 'strength'];
        yield 'endurance 0' => [[1, 0, 1, 1], 'endurance'];
        yield 'technique negative' => [[1, 1, -3, 1], 'technique'];
        yield 'charisma 0' => [[1, 1, 1, 0], 'charisma'];
    }

    /**
     * @param array{int, int, int, int} $values
     */
    #[DataProvider('invalidStats')]
    public function testRejectsStatBelowOne(array $values, string $field): void
    {
        try {
            new Stats(...$values);
            self::fail('Expected InvalidCharacterData');
        } catch (InvalidCharacterData $e) {
            self::assertSame($field, $e->field);
        }
    }
}
