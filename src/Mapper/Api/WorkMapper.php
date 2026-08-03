<?php

declare(strict_types=1);

namespace App\Mapper\Api;

use App\Dto\Api\Work\WorkCompleteResponse;
use App\Dto\Api\Work\WorkDto;
use App\Dto\Api\Work\WorkListResponse;
use App\Entity\Work;

final readonly class WorkMapper
{
    /**
     * @param array{
     *     totalGold: int,
     *     totalGoldAfterShip: int,
     *     perHourBaseGold: int,
     *     bonusPercent: int,
     *     shopBoosterPercent: int
     * } $rewards
     */
    public static function fromWork(Work $work, array $rewards, int $levelMultiplier): WorkDto
    {
        return new WorkDto(
            id: (int) $work->getId(),
            title: (string) $work->getTitle(),
            hoursCount: (int) $work->getHoursCount(),
            baseGold: (int) $work->getBaseGold(),
            effectiveBaseGold: $rewards['perHourBaseGold'],
            totalGoldAfterShip: $rewards['totalGoldAfterShip'],
            totalGoldPreview: $rewards['totalGold'],
            levelMultiplier: $levelMultiplier,
            bonusPercent: $rewards['bonusPercent'],
            shopBoosterPercent: $rewards['shopBoosterPercent'],
        );
    }

    /**
     * @param list<WorkDto> $works
     */
    public static function listResponse(array $works): WorkListResponse
    {
        return new WorkListResponse($works);
    }

    /**
     * @param list<WorkDto> $works
     */
    public static function completeResponse(int $earnedGold, int $bonusPercent, array $works): WorkCompleteResponse
    {
        return new WorkCompleteResponse($earnedGold, $bonusPercent, $works);
    }
}
