<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Domain\Model;

use App\Character\Domain\Exception\InvalidCharacterData;
use App\Character\Domain\Model\Experience;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExperienceTest extends TestCase
{
    public function testStartsWithNoPoints(): void
    {
        self::assertSame(0, Experience::none()->points());
    }

    public function testRejectsNegativePoints(): void
    {
        $this->expectException(InvalidCharacterData::class);

        new Experience(-1);
    }

    public function testGainAddsPointsWithoutChangingOriginal(): void
    {
        $experience = new Experience(40);

        self::assertSame(65, $experience->gain(25)->points());
        self::assertSame(40, $experience->points());
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidGains(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-5];
    }

    #[DataProvider('invalidGains')]
    public function testRejectsGainBelowOnePoint(int $points): void
    {
        try {
            Experience::none()->gain($points);
            self::fail('Expected InvalidCharacterData');
        } catch (InvalidCharacterData $e) {
            self::assertSame('experience', $e->field);
        }
    }

    public function testReachesThresholdFromExactValue(): void
    {
        self::assertTrue((new Experience(100))->reaches(100));
        self::assertTrue((new Experience(101))->reaches(100));
        self::assertFalse((new Experience(99))->reaches(100));
    }

    public function testSpendLeavesRemainder(): void
    {
        self::assertSame(30, (new Experience(130))->spend(100)->points());
    }
}
