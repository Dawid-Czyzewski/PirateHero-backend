<?php

declare(strict_types=1);

namespace App\Mapper\Api;

use App\Dto\Api\Mission\MissionCompleteResponse;
use App\Dto\Api\Mission\MissionDto;
use App\Dto\Api\Mission\MissionListResponse;
use App\Entity\Mission;

final readonly class MissionMapper
{
    /**
     * @param array{
     *     gold: int,
     *     exp: int,
     *     bonusPercent: int,
     *     shopBoosterPercent: int
     * } $rewards
     */
    public static function fromMission(Mission $mission, array $rewards): MissionDto
    {
        return new MissionDto(
            id: (int) $mission->getId(),
            title: (string) $mission->getTitle(),
            goldReward: $rewards['gold'],
            expReward: $rewards['exp'],
            baseGoldReward: (int) $mission->getGoldReward(),
            baseExpReward: (int) $mission->getExpReward(),
            bonusPercent: $rewards['bonusPercent'],
            shopBoosterPercent: $rewards['shopBoosterPercent'],
            durationInSeconds: (int) $mission->getDurationInSeconds(),
            energyCost: (int) $mission->getEnergyCost(),
        );
    }

    /**
     * @param list<MissionDto> $missions
     */
    public static function listResponse(array $missions): MissionListResponse
    {
        return new MissionListResponse($missions);
    }

    /**
     * @param list<MissionDto> $missions
     * @param array{earnedGold: int, earnedExp: int, bonusPercent: int, levelData: array<string, mixed>|null, diamondsSpent?: int} $result
     */
    public static function completeResponse(array $missions, array $result): MissionCompleteResponse
    {
        return new MissionCompleteResponse(
            missions: $missions,
            earnedGold: $result['earnedGold'],
            earnedExp: $result['earnedExp'],
            bonusPercent: $result['bonusPercent'],
            newLevel: $result['levelData'],
            diamondsSpent: (int) ($result['diamondsSpent'] ?? 0),
        );
    }
}
