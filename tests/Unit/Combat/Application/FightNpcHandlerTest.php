<?php

declare(strict_types=1);

namespace App\Tests\Unit\Combat\Application;

use App\Character\Domain\Exception\CharacterNotFound;
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

final class FightNpcHandlerTest extends TestCase
{
    private const USER_ID = '01a1a31d-3df0-7194-80d3-f04533ffb060';

    private InMemoryCharacterRepository $characters;

    protected function setUp(): void
    {
        $this->characters = new InMemoryCharacterRepository();
    }

    public function testFightsTheChosenNpcWithThePlayersCharacter(): void
    {
        $this->characters->save(Character::create(
            CharacterId::fromString('01a1a3a0-0000-7000-8000-000000000001'),
            UserId::fromString(self::USER_ID),
            new Appearance(0, 0, 0, 0, 0),
            new \DateTimeImmutable('2026-11-22 12:00:00'),
        ));

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
        self::assertSame(0, $view->staminaCost);
    }

    public function testFailsWithoutCharacter(): void
    {
        $this->expectException(CharacterNotFound::class);

        $this->handler()(new FightNpcCommand(self::USER_ID, 'kosa'));
    }

    public function testFailsForUnknownNpc(): void
    {
        $this->characters->save(Character::create(
            CharacterId::fromString('01a1a3a0-0000-7000-8000-000000000001'),
            UserId::fromString(self::USER_ID),
            new Appearance(0, 0, 0, 0, 0),
            new \DateTimeImmutable('2026-11-22 12:00:00'),
        ));

        $this->expectException(NpcNotFound::class);

        $this->handler()(new FightNpcCommand(self::USER_ID, 'nikt'));
    }

    private function handler(): FightNpcHandler
    {
        return new FightNpcHandler(
            $this->characters,
            new InMemoryPlayerNicks([self::USER_ID => 'Cebulak']),
            new NpcCatalog(),
            new FightEngine(SequenceRandomIntProvider::always(0)),
            new RewardPolicy(),
        );
    }
}
