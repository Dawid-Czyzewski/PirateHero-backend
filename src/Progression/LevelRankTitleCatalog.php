<?php

declare(strict_types=1);

namespace App\Progression;


final class LevelRankTitleCatalog
{
    public const STEP = 5;

    public const MIN_LEVEL = 5;

    public const MAX_LEVEL = 100;

    public const SORT_ORDER_BASE = 100;

    /**
     * @return list<array{code: string, level: int, nameKey: string, descriptionKey: string, sortOrder: int}>
     */
    public static function definitions(): array
    {
        $out = [];
        for ($level = self::MIN_LEVEL; $level <= self::MAX_LEVEL; $level += self::STEP) {
            $code = self::codeForLevel($level);
            $out[] = [
                'code' => $code,
                'level' => $level,
                'nameKey' => 'titles.'.$code.'.name',
                'descriptionKey' => 'titles.'.$code.'.unlockHint',
                'sortOrder' => self::SORT_ORDER_BASE + intdiv($level, self::STEP),
            ];
        }

        return $out;
    }

    public static function codeForLevel(int $level): string
    {
        return 'lvl_rank_'.$level;
    }

    public static function count(): int
    {
        return intdiv(self::MAX_LEVEL - self::MIN_LEVEL, self::STEP) + 1;
    }
}
