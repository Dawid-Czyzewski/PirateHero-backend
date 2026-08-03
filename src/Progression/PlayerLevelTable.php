<?php

declare(strict_types=1);

namespace App\Progression;

use App\Domain\Constants\ProgressionConstants;


final class PlayerLevelTable
{
    public const MAX_LEVEL = ProgressionConstants::MAX_LEVEL;


    public static function expToNextLevel(int $level): int
    {
        if ($level < 1) {
            throw new \InvalidArgumentException('Level must be >= 1');
        }

        return match ($level) {
            1 => 220,
            2 => 500,
            default => (400 * $level) - 300,
        };
    }

    /**
     * @return list<array{name: string, expToNextLevel: int}>
     */
    public static function rows(int $maxLevel = self::MAX_LEVEL): array
    {
        $max = max(1, $maxLevel);
        $rows = [];
        for ($level = 1; $level <= $max; ++$level) {
            $rows[] = [
                'name' => (string) $level,
                'expToNextLevel' => self::expToNextLevel($level),
            ];
        }

        return $rows;
    }
}
