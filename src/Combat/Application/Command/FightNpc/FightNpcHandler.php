<?php

declare(strict_types=1);

namespace App\Combat\Application\Command\FightNpc;

use App\Character\Application\Player\PlayerNicks;
use App\Character\Domain\Exception\CharacterNotFound;
use App\Character\Domain\Model\Character;
use App\Character\Domain\Repository\CharacterRepository;
use App\Combat\Domain\Catalog\NpcCatalog;
use App\Combat\Domain\Exception\NpcNotFound;
use App\Combat\Domain\Model\Fighter;
use App\Combat\Domain\Service\FightEngine;
use App\Combat\Domain\Service\RewardPolicy;
use App\Shared\Domain\Model\UserId;

final readonly class FightNpcHandler
{
    public function __construct(
        private CharacterRepository $characters,
        private PlayerNicks $nicks,
        private NpcCatalog $npcs,
        private FightEngine $engine,
        private RewardPolicy $rewards,
    ) {
    }

    /**
     * @throws CharacterNotFound
     * @throws NpcNotFound
     */
    public function __invoke(FightNpcCommand $command): FightView
    {
        $userId = UserId::fromString($command->userId);
        $character = $this->characters->ofUserId($userId) ?? throw CharacterNotFound::forUser($userId);
        $npc = $this->npcs->ofId($command->npcId);

        $player = self::fighterOf($character, $this->nicks->nickOf($userId));
        $result = $this->engine->fight($player, $npc->fighter);

        return FightView::of($player, $npc, $result, $this->rewards->for($result, $npc->fighter));
    }

    private static function fighterOf(Character $character, string $nick): Fighter
    {
        $stats = $character->stats();

        return new Fighter(
            $nick,
            $character->level()->value(),
            $stats->strength(),
            $stats->endurance(),
            $stats->technique(),
            $stats->charisma(),
        );
    }
}
