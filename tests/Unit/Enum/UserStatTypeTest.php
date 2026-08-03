<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\UserStatType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UserStatTypeTest extends TestCase
{
    public static function canonicalRequestProvider(): iterable
    {
        yield ['STRENGTH', UserStatType::STRENGTH];
        yield ['strength', UserStatType::STRENGTH];
        yield ['AGILITY', UserStatType::AGILITY];
        yield ['INTELLIGENCE', UserStatType::INTELLIGENCE];
        yield ['ENDURANCE', UserStatType::ENDURANCE];
        yield ['LUCK', UserStatType::LUCK];
    }

    #[DataProvider('canonicalRequestProvider')]
    public function testFromRequestParsesCanonicalAndLowercaseAliases(string $raw, UserStatType $expected): void
    {
        self::assertSame($expected, UserStatType::fromRequest($raw));
    }

    public function testFromRequestMapsLegacyCriticalChanceToIntelligence(): void
    {
        self::assertSame(UserStatType::INTELLIGENCE, UserStatType::fromRequest('CRITICAL_CHANCE'));
        self::assertSame(UserStatType::INTELLIGENCE, UserStatType::fromRequest('  critical_chance  '));
    }

    public function testFromRequestMapsLegacyHealthToEndurance(): void
    {
        self::assertSame(UserStatType::ENDURANCE, UserStatType::fromRequest('HEALTH'));
    }

    public function testFromRequestRejectsUnknownValue(): void
    {
        $this->expectException(\ValueError::class);
        UserStatType::fromRequest('UNKNOWN_STAT');
    }
}
