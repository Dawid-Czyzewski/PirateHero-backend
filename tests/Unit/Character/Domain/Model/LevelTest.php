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
