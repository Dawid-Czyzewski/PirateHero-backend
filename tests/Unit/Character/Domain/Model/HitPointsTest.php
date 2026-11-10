<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Domain\Model;

use App\Character\Domain\Model\HitPoints;
use App\Character\Domain\Model\Level;
use App\Character\Domain\Model\Stats;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HitPointsTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function maximumHitPoints(): iterable
    {
        yield 'starting character' => [1, 1, 15];
        yield 'endurance 7, level 3' => [7, 3, 85];
        yield 'endurance 1, level 10' => [1, 10, 60];
        yield 'endurance 20, level 1' => [20, 1, 205];
    }

    #[DataProvider('maximumHitPoints')]
    public function testMaximumIsEnduranceTimesTenPlusLevelTimesFive(int $endurance, int $level, int $expected): void
    {
        $stats = new Stats(strength: 1, endurance: $endurance, technique: 1, charisma: 1);

        self::assertSame($expected, HitPoints::maximum($stats, new Level($level))->value());
    }

    public function testDependsOnlyOnEnduranceAndLevel(): void
    {
        $weak = new Stats(strength: 1, endurance: 5, technique: 1, charisma: 1);
        $strong = new Stats(strength: 50, endurance: 5, technique: 50, charisma: 50);

        self::assertSame(
            HitPoints::maximum($weak, new Level(2))->value(),
            HitPoints::maximum($strong, new Level(2))->value(),
        );
    }
}
