<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Domain\Model;

use App\Character\Domain\Exception\InsufficientStamina;
use App\Character\Domain\Exception\InvalidCharacterData;
use App\Character\Domain\Model\Stamina;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StaminaTest extends TestCase
{
    private const START = '2026-11-19 12:00:00';

    public function testStartsFullWithoutTimer(): void
    {
        $stamina = Stamina::full(self::at(self::START));

        self::assertSame(100, $stamina->current(self::at(self::START)));
        self::assertNull($stamina->secondsToNextPoint(self::at('2026-11-19 15:00:00')));
    }

    /**
     * @return iterable<string, array{string, int, int|null}>
     */
    public static function regeneration(): iterable
    {
        yield 'right away' => ['2026-11-19 12:00:00', 40, 180];
        yield 'after 2:59' => ['2026-11-19 12:02:59', 40, 1];
        yield 'after 3:00' => ['2026-11-19 12:03:00', 41, 180];
        yield 'after 4:30' => ['2026-11-19 12:04:30', 41, 90];
        yield 'after 61 min' => ['2026-11-19 13:01:00', 60, 120];
        yield 'after 3 hours' => ['2026-11-19 15:00:00', 100, null];
        yield 'after a week' => ['2026-11-26 12:00:00', 100, null];
    }

    #[DataProvider('regeneration')]
    public function testRegeneratesOnePointEveryThreeMinutesUpToHundred(string $now, int $current, ?int $nextPoint): void
    {
        $stamina = new Stamina(40, self::at(self::START));

        self::assertSame($current, $stamina->current(self::at($now)));
        self::assertSame($nextPoint, $stamina->secondsToNextPoint(self::at($now)));
    }

    public function testSpendKeepsProgressTowardsTheNextPoint(): void
    {
        $stamina = (new Stamina(40, self::at(self::START)))->spend(10, self::at('2026-11-19 12:04:30'));

        self::assertSame(31, $stamina->current(self::at('2026-11-19 12:04:30')));
        self::assertSame(90, $stamina->secondsToNextPoint(self::at('2026-11-19 12:04:30')));
        self::assertSame(32, $stamina->current(self::at('2026-11-19 12:06:00')));
    }

    public function testSpendingFromFullStartsTheTimerAtSpendTime(): void
    {
        $stamina = Stamina::full(self::at(self::START))->spend(10, self::at('2026-11-19 14:00:00'));

        self::assertSame(90, $stamina->current(self::at('2026-11-19 14:02:59')));
        self::assertSame(91, $stamina->current(self::at('2026-11-19 14:03:00')));
    }

    public function testCanSpendEverythingThatIsLeft(): void
    {
        $stamina = (new Stamina(10, self::at(self::START)))->spend(10, self::at(self::START));

        self::assertSame(0, $stamina->current(self::at(self::START)));
    }

    public function testRejectsSpendingMoreThanAvailable(): void
    {
        try {
            (new Stamina(5, self::at(self::START)))->spend(10, self::at('2026-11-19 12:02:00'));
            self::fail('Expected InsufficientStamina');
        } catch (InsufficientStamina $e) {
            self::assertSame(10, $e->cost);
            self::assertSame(5, $e->available);
        }
    }

    public function testCountsRegenerationBeforeCheckingTheCost(): void
    {
        $stamina = (new Stamina(8, self::at(self::START)))->spend(10, self::at('2026-11-19 12:06:00'));

        self::assertSame(0, $stamina->current(self::at('2026-11-19 12:06:00')));
    }

    public function testRejectsCostBelowOne(): void
    {
        $this->expectException(InvalidCharacterData::class);

        Stamina::full(self::at(self::START))->spend(0, self::at(self::START));
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function outOfRangePoints(): iterable
    {
        yield 'negative' => [-1];
        yield 'above max' => [101];
    }

    #[DataProvider('outOfRangePoints')]
    public function testRejectsPointsOutsideRange(int $points): void
    {
        try {
            new Stamina($points, self::at(self::START));
            self::fail('Expected InvalidCharacterData');
        } catch (InvalidCharacterData $e) {
            self::assertSame('stamina', $e->field);
        }
    }

    private static function at(string $time): \DateTimeImmutable
    {
        return new \DateTimeImmutable($time);
    }
}
