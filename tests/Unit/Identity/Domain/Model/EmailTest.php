<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\Model;

use App\Identity\Domain\Exception\InvalidUserData;
use App\Identity\Domain\Model\Email;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testIsTrimmedAndLowercased(): void
    {
        self::assertSame('gracz@example.com', (new Email('  Gracz@Example.COM '))->toString());
    }

    public function testEqualityIgnoresCase(): void
    {
        self::assertTrue((new Email('gracz@example.com'))->equals(new Email('GRACZ@example.com')));
        self::assertFalse((new Email('gracz@example.com'))->equals(new Email('inny@example.com')));
    }

    public function testAcceptsMaxLength(): void
    {
        $value = 'gracz@'.str_repeat('a', 63).'.'.str_repeat('b', 63).'.'.str_repeat('c', 43).'.pl';
        self::assertSame(Email::MAX_LENGTH, \strlen($value));

        self::assertSame($value, (new Email($value))->toString());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidEmails(): iterable
    {
        yield 'empty' => [''];
        yield 'only spaces' => ['   '];
        yield 'no at' => ['gracz.example.com'];
        yield 'no domain' => ['gracz@'];
        yield 'no local part' => ['@example.com'];
        yield 'too long' => [str_repeat('a', Email::MAX_LENGTH).'@example.com'];
    }

    #[DataProvider('invalidEmails')]
    public function testRejectsInvalidValue(string $value): void
    {
        try {
            new Email($value);
            self::fail('Expected InvalidUserData');
        } catch (InvalidUserData $e) {
            self::assertSame('email', $e->field);
        }
    }
}
