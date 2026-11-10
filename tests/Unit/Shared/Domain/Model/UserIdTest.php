<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Domain\Model;

use App\Character\Domain\Model\CharacterId;
use App\Shared\Domain\Exception\InvalidIdentifier;
use App\Shared\Domain\Model\UserId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UserIdTest extends TestCase
{
    private const UUID = '01a1a31d-3df0-7194-80d3-f04533ffb060';

    public function testIsNormalizedToLowercase(): void
    {
        self::assertSame(self::UUID, UserId::fromString(strtoupper(self::UUID))->toString());
    }

    public function testIsStringable(): void
    {
        self::assertSame(self::UUID, (string) UserId::fromString(self::UUID));
    }

    public function testEquality(): void
    {
        self::assertTrue(UserId::fromString(self::UUID)->equals(UserId::fromString(strtoupper(self::UUID))));
        self::assertFalse(UserId::fromString(self::UUID)->equals(UserId::fromString('01a1a31d-3df0-7194-80d3-f04533ffb061')));
    }

    public function testIsNotEqualToOtherIdTypeWithSameValue(): void
    {
        self::assertFalse(UserId::fromString(self::UUID)->equals(CharacterId::fromString(self::UUID)));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidIds(): iterable
    {
        yield 'empty' => [''];
        yield 'number' => ['123'];
        yield 'no dashes' => ['01a1a31d3df0719480d3f04533ffb060'];
        yield 'not hex' => ['zzzzzzzz-3df0-7194-80d3-f04533ffb060'];
        yield 'too long' => [self::UUID.'0'];
    }

    #[DataProvider('invalidIds')]
    public function testRejectsInvalidValue(string $value): void
    {
        $this->expectException(InvalidIdentifier::class);

        UserId::fromString($value);
    }
}
