<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Domain\Model;

use App\Character\Domain\Exception\InvalidCharacterData;
use App\Character\Domain\Model\Appearance;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AppearanceTest extends TestCase
{
    public function testKeepsVariants(): void
    {
        $appearance = new Appearance(face: 1, hair: 2, beard: 3, skin: 4, tattoo: 5);

        self::assertSame(1, $appearance->face());
        self::assertSame(2, $appearance->hair());
        self::assertSame(3, $appearance->beard());
        self::assertSame(4, $appearance->skin());
        self::assertSame(5, $appearance->tattoo());
    }

    public function testAcceptsLowestAndHighestVariants(): void
    {
        $lowest = new Appearance(0, 0, 0, 0, 0);
        $highest = new Appearance(7, 7, 7, 4, 7);

        self::assertSame(0, $lowest->face());
        self::assertSame(7, $highest->face());
        self::assertSame(4, $highest->skin());
        self::assertSame(7, $highest->tattoo());
    }

    public function testExposesVariantCounts(): void
    {
        self::assertSame(8, Appearance::FACE_VARIANTS);
        self::assertSame(8, Appearance::HAIR_VARIANTS);
        self::assertSame(8, Appearance::BEARD_VARIANTS);
        self::assertSame(5, Appearance::SKIN_VARIANTS);
        self::assertSame(8, Appearance::TATTOO_VARIANTS);
    }

    /**
     * @return iterable<string, array{array{int, int, int, int, int}, string}>
     */
    public static function invalidVariants(): iterable
    {
        yield 'face 8' => [[8, 0, 0, 0, 0], 'face'];
        yield 'face negative' => [[-1, 0, 0, 0, 0], 'face'];
        yield 'hair 8' => [[0, 8, 0, 0, 0], 'hair'];
        yield 'beard 8' => [[0, 0, 8, 0, 0], 'beard'];
        yield 'skin 5' => [[0, 0, 0, 5, 0], 'skin'];
        yield 'skin negative' => [[0, 0, 0, -1, 0], 'skin'];
        yield 'tattoo 8' => [[0, 0, 0, 0, 8], 'tattoo'];
    }

    /**
     * @param array{int, int, int, int, int} $values
     */
    #[DataProvider('invalidVariants')]
    public function testRejectsVariantOutOfRange(array $values, string $field): void
    {
        try {
            new Appearance(...$values);
            self::fail('Expected InvalidCharacterData');
        } catch (InvalidCharacterData $e) {
            self::assertSame($field, $e->field);
        }
    }
}
