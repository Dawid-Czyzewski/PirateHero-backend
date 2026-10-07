<?php

declare(strict_types=1);

namespace App\Enum;

enum TreasureMapStepType: string
{
    case Missions = 'missions';
    case ArenaWins = 'arena_wins';
    case DungeonStageWin = 'dungeon_stage_win';
    case GoldSpent = 'gold_spent';
    case ShipVoyageComplete = 'ship_voyage_complete';
}
