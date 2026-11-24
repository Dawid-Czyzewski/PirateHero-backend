<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Domain\Model;

use App\Character\Domain\Exception\InsufficientStamina;
use App\Character\Domain\Exception\InvalidCharacterData;
use App\Character\Domain\Model\Appearance;
use App\Character\Domain\Model\Character;
use App\Character\Domain\Model\CharacterId;
use App\Character\Domain\Model\Stat;
use App\Shared\Domain\Model\UserId;
use PHPUnit\Framework\Attributes\DataProvider;
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
        self::assertSame(0, $wallet->reach());
        self::assertSame(0, $wallet->medals());
    }

    public function testCreateStartsWithFullStamina(): void
    {
        $stamina = $this->create()->stamina();

        self::assertSame(100, $stamina->current(new \DateTimeImmutable('2026-11-19 12:00:00')));
        self::assertNull($stamina->secondsToNextPoint(new \DateTimeImmutable('2026-11-19 12:00:00')));
    }

    public function testSpendingStaminaLowersItFromTheMomentOfSpending(): void
    {
        $character = $this->create();

        $character->spendStamina(10, new \DateTimeImmutable('2026-11-19 13:00:00'));

        self::assertSame(90, $character->stamina()->current(new \DateTimeImmutable('2026-11-19 13:00:00')));
        self::assertSame(91, $character->stamina()->current(new \DateTimeImmutable('2026-11-19 13:03:00')));
    }

    public function testCreateStartsWithFullFightPoints(): void
    {
        $fightPoints = $this->create()->fightPoints();

        self::assertSame(10, $fightPoints->current(new \DateTimeImmutable('2026-11-19 12:00:00')));
        self::assertNull($fightPoints->secondsToNextPoint(new \DateTimeImmutable('2026-11-19 12:00:00')));
    }

    public function testSpendingFightPointsLeavesStaminaUntouched(): void
    {
        $character = $this->create();

        $character->spendFightPoints(1, new \DateTimeImmutable('2026-11-19 13:00:00'));

        self::assertSame(9, $character->fightPoints()->current(new \DateTimeImmutable('2026-11-19 13:00:00')));
        self::assertSame(10, $character->fightPoints()->current(new \DateTimeImmutable('2026-11-19 13:15:00')));
        self::assertSame(100, $character->stamina()->current(new \DateTimeImmutable('2026-11-19 13:00:00')));
    }

    public function testEarnsOnionsIntoTheWallet(): void
    {
        $character = $this->create();

        $character->earnOnions(25);

        self::assertSame(125, $character->wallet()->onions());
    }

    /**
     * @return iterable<string, array{Stat}>
     */
    public static function stats(): iterable
    {
        foreach (Stat::cases() as $stat) {
            yield $stat->value => [$stat];
        }
    }

    #[DataProvider('stats')]
    public function testTrainingCostsTenStaminaAndRaisesTheStat(Stat $stat): void
    {
        $character = $this->create();

        $character->train($stat, new \DateTimeImmutable('2026-11-19 13:00:00'));

        self::assertSame(2, $character->stats()->of($stat));
        self::assertSame(90, $character->stamina()->current(new \DateTimeImmutable('2026-11-19 13:00:00')));
    }

    public function testTrainingEnduranceRaisesMaxHp(): void
    {
        $character = $this->create();

        $character->train(Stat::Endurance, new \DateTimeImmutable('2026-11-19 13:00:00'));

        self::assertSame(25, $character->maxHp());
    }

    public function testCannotTrainWithoutEnoughStaminaAndNothingChanges(): void
    {
        $character = $this->create();
        $character->spendStamina(95, new \DateTimeImmutable('2026-11-19 13:00:00'));

        try {
            $character->train(Stat::Strength, new \DateTimeImmutable('2026-11-19 13:00:00'));
            self::fail('Expected InsufficientStamina');
        } catch (InsufficientStamina $e) {
            self::assertSame(10, $e->cost);
            self::assertSame(5, $e->available);
        }
        self::assertSame(1, $character->stats()->strength());
        self::assertSame(5, $character->stamina()->current(new \DateTimeImmutable('2026-11-19 13:00:00')));
    }

    public function testStaminaRegeneratesAfterTraining(): void
    {
        $character = $this->create();

        $character->train(Stat::Technique, new \DateTimeImmutable('2026-11-19 13:00:00'));

        self::assertSame(90, $character->stamina()->current(new \DateTimeImmutable('2026-11-19 13:02:59')));
        self::assertSame(91, $character->stamina()->current(new \DateTimeImmutable('2026-11-19 13:03:00')));
        self::assertSame(100, $character->stamina()->current(new \DateTimeImmutable('2026-11-19 13:30:00')));
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
            new \DateTimeImmutable('2026-11-19 12:00:00'),
        );
    }
}
