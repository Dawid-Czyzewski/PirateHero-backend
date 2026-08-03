<?php

declare(strict_types=1);

namespace App\Config;

use App\Domain\Constants\RewardConstants;

/**
 * 30-day login reward schedule — one reward per day, same rules for day 30.
 */
final class DailyRewardCatalog
{
    /** @deprecated use {@see RewardConstants::DAILY_TOTAL_DAYS} */
    public const TOTAL_DAYS = RewardConstants::DAILY_TOTAL_DAYS;

    /**
     * @return list<array{day: int, rewards: list<array{type: string, amount?: int}>}>
     */
    public static function getSchedule(): array
    {
        $days = [];
        for ($day = 1; $day <= RewardConstants::DAILY_TOTAL_DAYS; ++$day) {
            $days[] = [
                'day' => $day,
                'rewards' => self::rewardsForDay($day),
            ];
        }

        return $days;
    }

    /**
     * @return list<array{type: string, amount?: int}>
     */
    public static function rewardsForDay(int $day): array
    {
        return [self::rewardEntryForDay($day)];
    }

    /**
     * @return array{type: string, amount?: int}
     */
    private static function rewardEntryForDay(int $day): array
    {
        return match ($day % RewardConstants::DAILY_REWARD_CYCLE) {
            0 => [
                'type' => 'gold',
                'amount' => RewardConstants::DAILY_GOLD_BASE + $day * RewardConstants::DAILY_GOLD_PER_DAY,
            ],
            1 => [
                'type' => 'experience',
                'amount' => RewardConstants::DAILY_EXP_BASE + $day * RewardConstants::DAILY_EXP_PER_DAY,
            ],
            2 => [
                'type' => 'diamonds',
                'amount' => RewardConstants::DAILY_DIAMONDS_BASE + $day * RewardConstants::DAILY_DIAMONDS_PER_DAY,
            ],
            default => ['type' => 'item'],
        };
    }
}
