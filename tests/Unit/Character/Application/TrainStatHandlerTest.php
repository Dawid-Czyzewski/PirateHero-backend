<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Application;

use App\Character\Application\Command\TrainStat\TrainStatCommand;
use App\Character\Application\Command\TrainStat\TrainStatHandler;
use App\Character\Domain\Exception\CharacterNotFound;
use App\Character\Domain\Exception\InsufficientStamina;
use App\Character\Domain\Model\Appearance;
use App\Character\Domain\Model\Character;
use App\Character\Domain\Model\CharacterId;
use App\Character\Domain\Model\Stat;
use App\Shared\Domain\Model\UserId;
use App\Tests\Double\Character\InMemoryCharacterRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class TrainStatHandlerTest extends TestCase
{
    private const USER_ID = '01a1a31d-3df0-7194-80d3-f04533ffb060';
    private const CHARACTER_ID = '01a1a3a0-0000-7000-8000-000000000001';

    private InMemoryCharacterRepository $characters;
    private MockClock $clock;
    private TrainStatHandler $handler;

    protected function setUp(): void
    {
        $this->characters = new InMemoryCharacterRepository();
        $this->clock = new MockClock(new \DateTimeImmutable('2026-11-19 12:00:00'));
        $this->handler = new TrainStatHandler($this->characters, $this->clock);
    }

    public function testTrainsTheChosenStatOfThePlayersCharacter(): void
    {
        $this->characters->save($this->character());

        ($this->handler)(new TrainStatCommand(self::USER_ID, Stat::Charisma));

        $character = $this->characters->ofUserId(UserId::fromString(self::USER_ID));
        self::assertNotNull($character);
        self::assertSame(2, $character->stats()->charisma());
        self::assertSame(90, $character->stamina()->current($this->clock->now()));
    }

    public function testUsesTheClockForRegeneration(): void
    {
        $this->characters->save($this->character());

        ($this->handler)(new TrainStatCommand(self::USER_ID, Stat::Strength));
        $this->clock->sleep(180);

        $character = $this->characters->ofUserId(UserId::fromString(self::USER_ID));
        self::assertNotNull($character);
        self::assertSame(91, $character->stamina()->current($this->clock->now()));
    }

    public function testStopsWhenStaminaRunsOut(): void
    {
        $this->characters->save($this->character());
        for ($i = 0; $i < 10; ++$i) {
            ($this->handler)(new TrainStatCommand(self::USER_ID, Stat::Strength));
        }

        $this->expectException(InsufficientStamina::class);

        ($this->handler)(new TrainStatCommand(self::USER_ID, Stat::Strength));
    }

    public function testFailsWithoutCharacter(): void
    {
        $this->expectException(CharacterNotFound::class);

        ($this->handler)(new TrainStatCommand(self::USER_ID, Stat::Strength));
    }

    private function character(): Character
    {
        return Character::create(
            CharacterId::fromString(self::CHARACTER_ID),
            UserId::fromString(self::USER_ID),
            new Appearance(0, 0, 0, 0, 0),
            $this->clock->now(),
        );
    }
}
