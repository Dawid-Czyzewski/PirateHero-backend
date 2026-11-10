<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\Model;

use App\Identity\Domain\Exception\InvalidUserData;
use App\Identity\Domain\Model\Nick;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NickTest extends TestCase
{
    public function testIsTrimmedButKeepsCase(): void
    {
        self::assertSame('Cebulak', (new Nick('  Cebulak '))->toString());
    }

    public function testEqualityIsCaseSensitive(): void
    {
        self::assertTrue((new Nick('Cebulak'))->equals(new Nick('Cebulak')));
        self::assertFalse((new Nick('Cebulak'))->equals(new Nick('cebulak')));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validNicks(): iterable
    {
        yield '3 chars (min)' => ['abc'];
        yield '16 chars (max)' => [str_repeat('a', 16)];
        yield 'polish letters count as one char' => ['Żółć'];
        yield 'digits and underscore' => ['Freak_2026'];
    }

    #[DataProvider('validNicks')]
    public function testAcceptsValidValue(string $value): void
    {
        self::assertSame($value, (new Nick($value))->toString());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidNicks(): iterable
    {
        yield 'empty' => [''];
        yield 'only spaces' => ['     '];
        yield '2 chars' => ['ab'];
        yield '2 chars after trim' => ['  ab  '];
        yield '17 chars' => [str_repeat('a', 17)];
    }

    #[DataProvider('invalidNicks')]
    public function testRejectsInvalidValue(string $value): void
    {
        try {
            new Nick($value);
            self::fail('Expected InvalidUserData');
        } catch (InvalidUserData $e) {
            self::assertSame('nick', $e->field);
        }
    }
}
