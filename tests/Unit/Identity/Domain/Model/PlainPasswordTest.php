<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\Model;

use App\Identity\Domain\Exception\InvalidUserData;
use App\Identity\Domain\Model\HashedPassword;
use App\Identity\Domain\Model\PlainPassword;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PlainPasswordTest extends TestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function validPasswords(): iterable
    {
        yield '8 chars (min)' => ['12345678'];
        yield 'spaces are kept' => [' tajne haslo '];
        yield 'max length' => [str_repeat('a', PlainPassword::MAX_LENGTH)];
    }

    #[DataProvider('validPasswords')]
    public function testKeepsValueAsTyped(string $value): void
    {
        self::assertSame($value, (new PlainPassword($value))->reveal());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidPasswords(): iterable
    {
        yield 'empty' => [''];
        yield '7 chars' => ['1234567'];
        yield '7 multibyte chars' => ['żółćżół'];
        yield 'too long' => [str_repeat('a', PlainPassword::MAX_LENGTH + 1)];
    }

    #[DataProvider('invalidPasswords')]
    public function testRejectsInvalidValue(string $value): void
    {
        try {
            new PlainPassword($value);
            self::fail('Expected InvalidUserData');
        } catch (InvalidUserData $e) {
            self::assertSame('password', $e->field);
        }
    }

    public function testValueIsHiddenInStackTraces(): void
    {
        $parameter = (new \ReflectionMethod(PlainPassword::class, '__construct'))->getParameters()[0];

        self::assertNotEmpty($parameter->getAttributes(\SensitiveParameter::class));
    }

    public function testHashedPasswordCannotBeEmpty(): void
    {
        $this->expectException(InvalidUserData::class);

        new HashedPassword('');
    }
}
