<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Domain\Model;

use App\Character\Domain\Model\Appearance;
use App\Character\Domain\Model\Character;
use App\Character\Domain\Model\CharacterId;
use App\Shared\Domain\Model\UserId;
use PHPUnit\Framework\TestCase;

final class CharacterTest extends TestCase
{
    private const CHARACTER_ID = '01a1a3a0-0000-7000-8000-000000000001';
    private const USER_ID = '01a1a31d-3df0-7194-80d3-f04533ffb060';

    public function testCreateKeepsIdentityOwnerAndAppearance(): void
    {
        $appearance = new Appearance(face: 3, hair: 1, beard: 7, skin: 2, tattoo: 0);

        $character = $this->create($appearance);

        self::assertTrue($character->id()->equals(CharacterId::fromString(self::CHARACTER_ID)));
        self::assertTrue($character->userId()->equals(UserId::fromString(self::USER_ID)));
        self::assertSame($appearance, $character->appearance());
    }

    public function testCreateStartsAtLevelOne(): void
    {
        self::assertSame(1, $this->create()->level()->value());
    }

    public function testCreateStartsWithAllStatsAtOne(): void
    {
        $stats = $this->create()->stats();

        self::assertSame(1, $stats->strength());
        self::assertSame(1, $stats->endurance());
        self::assertSame(1, $stats->technique());
        self::assertSame(1, $stats->charisma());
    }

    public function testCreateStartsWithStartingWallet(): void
    {
        $wallet = $this->create()->wallet();

        self::assertSame(100, $wallet->cebuliony());
        self::assertSame(10, $wallet->zloteZeby());
        self::assertSame(100, $wallet->kondycha());
        self::assertSame(0, $wallet->zasiegi());
        self::assertSame(0, $wallet->medale());
    }

    public function testStartingMaxHpIsFifteen(): void
    {
        self::assertSame(15, $this->create()->maxHp());
    }

    private function create(?Appearance $appearance = null): Character
    {
        return Character::create(
            CharacterId::fromString(self::CHARACTER_ID),
            UserId::fromString(self::USER_ID),
            $appearance ?? new Appearance(0, 0, 0, 0, 0),
        );
    }
}
