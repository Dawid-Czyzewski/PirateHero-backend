<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Domain\Model;

use App\Character\Domain\Exception\InsufficientFightPoints;
use App\Character\Domain\Exception\InvalidCharacterData;
use App\Character\Domain\Model\FightPoints;
use PHPUnit\Framework\TestCase;

final class FightPointsTest extends TestCase
{
    private const NOW = '2026-11-24 12:00:00';

    public function testStartsFullWithoutTimer(): void
    {
        $points = FightPoints::full(self::at(self::NOW));

        self::assertSame(10, $points->current(self::at(self::NOW)));
        self::assertNull($points->secondsToNextPoint(self::at(self::NOW)));
    }

    public function testRegeneratesOnePointEveryFifteenMinutes(): void
    {
        $points = FightPoints::full(self::at(self::NOW))->spend(3, self::at(self::NOW));

        self::assertSame(7, $points->current(self::at(self::NOW)));
        self::assertSame(900, $points->secondsToNextPoint(self::at(self::NOW)));
        self::assertSame(7, $points->current(self::at('2026-11-24 12:14:59')));
        self::assertSame(8, $points->current(self::at('2026-11-24 12:15:00')));
        self::assertSame(10, $points->current(self::at('2026-11-24 13:00:00')));
        self::assertSame(10, $points->current(self::at('2026-11-25 12:00:00')));
    }

    public function testRefusesToSpendMoreThanAvailable(): void
    {
        $points = new FightPoints(0, self::at(self::NOW));

        try {
            $points->spend(1, self::at(self::NOW));
            self::fail('Expected missing fight points.');
        } catch (InsufficientFightPoints $e) {
            self::assertSame(1, $e->cost);
            self::assertSame(0, $e->available);
        }
    }

    public function testRejectsValuesOutsideTheRange(): void
    {
        $this->expectException(InvalidCharacterData::class);
        $this->expectExceptionMessage('FightPoints must be between 0 and 10, 11 given.');

        new FightPoints(11, self::at(self::NOW));
    }

    public function testRejectsSpendingNothing(): void
    {
        $this->expectException(InvalidCharacterData::class);

        FightPoints::full(self::at(self::NOW))->spend(0, self::at(self::NOW));
    }

    private static function at(string $time): \DateTimeImmutable
    {
        return new \DateTimeImmutable($time);
    }
}
