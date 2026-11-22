<?php

declare(strict_types=1);

namespace App\Combat\Application\Command\FightNpc;

use App\Combat\Domain\Model\CombatBalance;
use App\Combat\Domain\Model\Fighter;
use App\Combat\Domain\Model\FightResult;
use App\Combat\Domain\Model\Npc;
use App\Combat\Domain\Model\Reward;

final readonly class FightView
{
    /**
     * @param array{name: string, level: int, strength: int, endurance: int, technique: int, charisma: int, maxHp: int}                                                                                                    $player
     * @param array{id: string, look: array{face: int, hair: int, beard: int, skin: int, tattoo: int}, fighter: array{name: string, level: int, strength: int, endurance: int, technique: int, charisma: int, maxHp: int}} $npc
     * @param array{winner: string, knockout: bool, rounds: list<array{number: int, attacker: string, damage: int, playerHp: int, npcHp: int}>}                                                                            $result
     * @param array{experience: int, onions: int}                                                                                                                                                                          $reward
     */
    public function __construct(
        public array $player,
        public array $npc,
        public array $result,
        public array $reward,
        public int $staminaCost,
    ) {
    }

    public static function of(Fighter $player, Npc $npc, FightResult $result, Reward $reward): self
    {
        return new self(
            $player->toArray(),
            ['id' => $npc->id, 'look' => $npc->look->toArray(), 'fighter' => $npc->fighter->toArray()],
            $result->toArray(),
            $reward->toArray(),
            CombatBalance::STAMINA_COST,
        );
    }
}
