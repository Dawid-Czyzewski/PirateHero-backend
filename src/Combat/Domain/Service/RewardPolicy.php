<?php

declare(strict_types=1);

namespace App\Combat\Domain\Service;

use App\Combat\Domain\Model\CombatBalance;
use App\Combat\Domain\Model\Fighter;
use App\Combat\Domain\Model\FightResult;
use App\Combat\Domain\Model\Reward;

final readonly class RewardPolicy
{
    public function for(FightResult $result, Fighter $npc): Reward
    {
        $victoryExperience = CombatBalance::WIN_BASE_EXPERIENCE + CombatBalance::WIN_EXPERIENCE_PER_NPC_LEVEL * $npc->level();

        if ($result->playerWon()) {
            return new Reward(
                $victoryExperience,
                CombatBalance::WIN_BASE_ONIONS + CombatBalance::WIN_ONIONS_PER_NPC_LEVEL * $npc->level(),
            );
        }

        return new Reward(
            intdiv($victoryExperience * CombatBalance::LOSS_EXPERIENCE_PERCENT, 100),
            CombatBalance::LOSS_ONIONS,
        );
    }
}
