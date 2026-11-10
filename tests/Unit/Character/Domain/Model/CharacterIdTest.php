<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Domain\Model;

use App\Character\Domain\Model\CharacterId;
use App\Shared\Domain\Exception\InvalidIdentifier;
use PHPUnit\Framework\TestCase;

final class CharacterIdTest extends TestCase
{
    private const UUID = '01a1a3a0-0000-7000-8000-000000000001';

    public function testKeepsNormalizedUuid(): void
    {
        $id = CharacterId::fromString(strtoupper(self::UUID));

        self::assertSame(self::UUID, $id->toString());
        self::assertTrue($id->equals(CharacterId::fromString(self::UUID)));
    }

    public function testRejectsNonUuid(): void
    {
        $this->expectException(InvalidIdentifier::class);

        CharacterId::fromString('character-1');
    }
}
