<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Domain\Model;

use App\Character\Domain\Exception\InvalidCharacterData;
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

        self::assertSame(100, $wallet->onions());
        self::assertSame(10, $wallet->goldTeeth());
        self::assertSame(100, $wallet->energy());
        self::assertSame(0, $wallet->reach());
        self::assertSame(0, $wallet->medals());
    }

    public function testStartingMaxHpIsFifteen(): void
    {
        self::assertSame(15, $this->create()->maxHp());
    }

    public function testCreateStartsWithoutExperience(): void
    {
        self::assertSame(0, $this->create()->experience()->points());
    }

    public function testGainBelowThresholdKeepsLevel(): void
    {
        $character = $this->create();

        $character->gainExperience(60);

        self::assertSame(1, $character->level()->value());
        self::assertSame(60, $character->experience()->points());
    }

    public function testReachingThresholdLevelsUpWithEmptyProgress(): void
    {
        $character = $this->create();

        $character->gainExperience(100);

        self::assertSame(2, $character->level()->value());
        self::assertSame(0, $character->experience()->points());
    }

    public function testGainsAcrossCallsAddUp(): void
    {
        $character = $this->create();

        $character->gainExperience(70);
        $character->gainExperience(50);

        self::assertSame(2, $character->level()->value());
        self::assertSame(20, $character->experience()->points());
    }

    public function testLargeGainLevelsUpSeveralTimesAndCarriesRemainder(): void
    {
        $character = $this->create();

        $character->gainExperience(350);

        self::assertSame(3, $character->level()->value());
        self::assertSame(50, $character->experience()->points());
    }

    public function testLevelUpRaisesMaxHp(): void
    {
        $character = $this->create();

        $character->gainExperience(100);

        self::assertSame(20, $character->maxHp());
    }

    public function testRejectsGainBelowOnePointWithoutChangingProgress(): void
    {
        $character = $this->create();
        $character->gainExperience(30);

        try {
            $character->gainExperience(0);
            self::fail('Expected InvalidCharacterData');
        } catch (InvalidCharacterData) {
            self::assertSame(1, $character->level()->value());
            self::assertSame(30, $character->experience()->points());
        }
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
