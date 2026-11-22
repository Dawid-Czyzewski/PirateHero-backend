<?php

declare(strict_types=1);

namespace App\Tests\Unit\Combat\Domain;

use App\Character\Domain\Model\HitPoints;
use App\Character\Domain\Model\Level;
use App\Character\Domain\Model\Stats;
use App\Combat\Domain\Exception\InvalidFighter;
use App\Combat\Domain\Model\Fighter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FighterTest extends TestCase
{
    public function testMaxHpIsEnduranceTimesTenPlusLevelTimesFive(): void
    {
        self::assertSame(15, new Fighter('Gracz', 1, 1, 1, 1, 1)->maxHp());
        self::assertSame(70, new Fighter('Rekin', 4, 5, 5, 3, 2)->maxHp());
    }

    public function testMaxHpMatchesTheCharacterProfile(): void
    {
        $fighter = new Fighter('Gracz', 3, 2, 4, 1, 1);

        self::assertSame(HitPoints::maximum(new Stats(2, 4, 1, 1), new Level(3))->value(), $fighter->maxHp());
    }

    public function testBaseDamageIsStrengthTimesTwoPlusTechnique(): void
    {
        self::assertSame(13, new Fighter('Pała', 4, 5, 3, 3, 2)->baseDamage());
    }

    public function testExportsDataForTheFrontend(): void
    {
        self::assertSame(
            ['name' => 'Kosa', 'level' => 1, 'strength' => 1, 'endurance' => 2, 'technique' => 3, 'charisma' => 4, 'maxHp' => 25],
            new Fighter('Kosa', 1, 1, 2, 3, 4)->toArray(),
        );
    }

    /**
     * @return iterable<string, array{string, int, int}>
     */
    public static function invalidValues(): iterable
    {
        yield 'level' => ['Kosa', 0, 1];
        yield 'stat' => ['Kosa', 1, 0];
        yield 'blank name' => ['  ', 1, 1];
    }

    #[DataProvider('invalidValues')]
    public function testRejectsInvalidValues(string $name, int $level, int $strength): void
    {
        $this->expectException(InvalidFighter::class);

        new Fighter($name, $level, $strength, 1, 1, 1);
    }
}
