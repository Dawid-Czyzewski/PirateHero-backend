<?php

declare(strict_types=1);

namespace App\Combat\Application\Command\FightNpc;

use App\Character\Application\Player\PlayerNicks;
use App\Character\Domain\Exception\CharacterNotFound;
use App\Character\Domain\Exception\InsufficientFightPoints;
use App\Character\Domain\Model\Character;
use App\Character\Domain\Repository\CharacterRepository;
use App\Combat\Domain\Catalog\NpcCatalog;
use App\Combat\Domain\Exception\NpcNotFound;
use App\Combat\Domain\Model\CombatBalance;
use App\Combat\Domain\Model\Fighter;
use App\Combat\Domain\Model\Reward;
use App\Combat\Domain\Service\FightEngine;
use App\Combat\Domain\Service\RewardPolicy;
use App\Shared\Domain\Model\UserId;
use Psr\Clock\ClockInterface;

final readonly class FightNpcHandler
{
    public function __construct(
        private CharacterRepository $characters,
        private PlayerNicks $nicks,
        private NpcCatalog $npcs,
        private FightEngine $engine,
        private RewardPolicy $rewards,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws CharacterNotFound
     * @throws NpcNotFound
     * @throws InsufficientFightPoints
     */
    public function __invoke(FightNpcCommand $command): FightView
    {
        $userId = UserId::fromString($command->userId);
        $character = $this->characters->ofUserId($userId) ?? throw CharacterNotFound::forUser($userId);
        $npc = $this->npcs->ofId($command->npcId);
        $levelBefore = $character->level()->value();

        $character->spendFightPoints(CombatBalance::FIGHT_POINT_COST, $this->clock->now());
        $player = self::fighterOf($character, $this->nicks->nickOf($userId));
        $result = $this->engine->fight($player, $npc->fighter);
        $reward = $this->rewards->for($result, $npc->fighter);

        self::award($character, $reward);
        $this->characters->save($character);

        return FightView::of($player, $npc, $result, $reward, $levelBefore, $character->level()->value());
    }

    private static function award(Character $character, Reward $reward): void
    {
        $character->gainExperience($reward->experience);
        $character->earnOnions($reward->onions);
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
