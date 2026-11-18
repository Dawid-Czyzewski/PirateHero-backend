<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Domain\Model;

use App\Character\Domain\Exception\InvalidCharacterData;
use App\Character\Domain\Model\Level;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LevelTest extends TestCase
{
    public function testStartsAtOne(): void
    {
        self::assertSame(1, Level::start()->value());
    }

    public function testKeepsValidValue(): void
    {
        self::assertSame(42, (new Level(42))->value());
    }

    /**
     * @return iterable<string, array{int, int}>
     */
    public static function experienceThresholds(): iterable
    {
        yield 'level 1' => [1, 100];
        yield 'level 2' => [2, 200];
        yield 'level 10' => [10, 1000];
    }

    #[DataProvider('experienceThresholds')]
    public function testRequiresHundredExperiencePerLevel(int $level, int $required): void
    {
        self::assertSame($required, (new Level($level))->requiredExperience());
    }

    public function testNextLevelIsOneHigher(): void
    {
        $level = new Level(3);

        self::assertSame(4, $level->next()->value());
        self::assertSame(3, $level->value());
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidLevels(): iterable
    {
        yield 'zero' => [0];
        yield 'negative' => [-1];
    }

    #[DataProvider('invalidLevels')]
    public function testRejectsLevelBelowOne(int $value): void
    {
        try {
            new Level($value);
            self::fail('Expected InvalidCharacterData');
        } catch (InvalidCharacterData $e) {
            self::assertSame('level', $e->field);
        }
    }
}
