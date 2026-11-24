<?php

declare(strict_types=1);

namespace App\Tests\Unit\Combat\Application;

use App\Character\Domain\Exception\CharacterNotFound;
use App\Character\Domain\Exception\InsufficientFightPoints;
use App\Character\Domain\Model\Appearance;
use App\Character\Domain\Model\Character;
use App\Character\Domain\Model\CharacterId;
use App\Combat\Application\Command\FightNpc\FightNpcCommand;
use App\Combat\Application\Command\FightNpc\FightNpcHandler;
use App\Combat\Domain\Catalog\NpcCatalog;
use App\Combat\Domain\Exception\NpcNotFound;
use App\Combat\Domain\Service\FightEngine;
use App\Combat\Domain\Service\RewardPolicy;
use App\Shared\Domain\Model\UserId;
use App\Tests\Double\Character\InMemoryCharacterRepository;
use App\Tests\Double\Character\InMemoryPlayerNicks;
use App\Tests\Double\Combat\SequenceRandomIntProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class FightNpcHandlerTest extends TestCase
{
    private const USER_ID = '01a1a31d-3df0-7194-80d3-f04533ffb060';

    private InMemoryCharacterRepository $characters;
    private MockClock $clock;

    protected function setUp(): void
    {
        $this->characters = new InMemoryCharacterRepository();
        $this->clock = new MockClock(new \DateTimeImmutable('2026-11-23 12:00:00'));
    }

    public function testFightsTheChosenNpcWithThePlayersCharacter(): void
    {
        $this->characters->save($this->character());

        $view = $this->handler()(new FightNpcCommand(self::USER_ID, 'kosa'));

        self::assertSame('Cebulak', $view->player['name']);
        self::assertSame(15, $view->player['maxHp']);
        self::assertSame('kosa', $view->npc['id']);
        self::assertSame('Kosa', $view->npc['fighter']['name']);
        self::assertSame(['face' => 0, 'hair' => 2, 'beard' => 0, 'skin' => 1, 'tattoo' => 0], $view->npc['look']);
        self::assertSame('player', $view->result['winner']);
        self::assertTrue($view->result['knockout']);
        self::assertCount(9, $view->result['rounds']);
        self::assertSame(['experience' => 25, 'onions' => 15], $view->reward);
        self::assertSame(['before' => 1, 'after' => 1], $view->level);
        self::assertSame(1, $view->fightPointCost);
    }

    public function testSavesTheRewardInTheCharacter(): void
    {
        $this->characters->save($this->character());

        $this->handler()(new FightNpcCommand(self::USER_ID, 'kosa'));

        $character = $this->characters->ofUserId(UserId::fromString(self::USER_ID));
        self::assertNotNull($character);
        self::assertSame(25, $character->experience()->points());
        self::assertSame(115, $character->wallet()->onions());
        self::assertSame(9, $character->fightPoints()->current($this->clock->now()));
        self::assertSame(100, $character->stamina()->current($this->clock->now()));
    }

    public function testRefusesToFightWithoutFightPointsAndChangesNothing(): void
    {
        $character = $this->character();
        $character->spendFightPoints(10, $this->clock->now());
        $this->characters->save($character);

        try {
            $this->handler()(new FightNpcCommand(self::USER_ID, 'kosa'));
            self::fail('Expected missing fight points.');
        } catch (InsufficientFightPoints $e) {
            self::assertSame(1, $e->cost);
            self::assertSame(0, $e->available);
        }

        $saved = $this->characters->ofUserId(UserId::fromString(self::USER_ID));
        self::assertNotNull($saved);
        self::assertSame(0, $saved->experience()->points());
        self::assertSame(100, $saved->wallet()->onions());
    }

    public function testDefeatStillPaysExperienceAndCanLevelUp(): void
    {
        $character = $this->character();
        $character->gainExperience(95);
        $this->characters->save($character);

        $view = $this->handler()(new FightNpcCommand(self::USER_ID, 'krol-parkingu'));

        self::assertSame('npc', $view->result['winner']);
        self::assertSame(['experience' => 15, 'onions' => 0], $view->reward);
        self::assertSame(['before' => 1, 'after' => 2], $view->level);
        $saved = $this->characters->ofUserId(UserId::fromString(self::USER_ID));
        self::assertNotNull($saved);
        self::assertSame(100, $saved->wallet()->onions());
    }

    public function testFailsWithoutCharacter(): void
    {
        $this->expectException(CharacterNotFound::class);

        $this->handler()(new FightNpcCommand(self::USER_ID, 'kosa'));
    }

    public function testFailsForUnknownNpc(): void
    {
        $this->characters->save($this->character());

        $this->expectException(NpcNotFound::class);

        $this->handler()(new FightNpcCommand(self::USER_ID, 'nikt'));
    }

    private function character(): Character
    {
        return Character::create(
            CharacterId::fromString('01a1a3a0-0000-7000-8000-000000000001'),
            UserId::fromString(self::USER_ID),
            new Appearance(0, 0, 0, 0, 0),
            $this->clock->now(),
        );
    }

    private function handler(): FightNpcHandler
    {
        return new FightNpcHandler(
            $this->characters,
            new InMemoryPlayerNicks([self::USER_ID => 'Cebulak']),
            new NpcCatalog(),
            new FightEngine(SequenceRandomIntProvider::always(0)),
            new RewardPolicy(),
            $this->clock,
        );
    }
}
