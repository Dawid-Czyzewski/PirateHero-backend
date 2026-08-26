<?php

declare(strict_types=1);

namespace App\Bestiary;

final class BestiaryTrophyCatalog
{
    public const TOTAL_ENTRIES = 50;

    /**
     * @return list<array{
     *     code: string,
     *     targetCount: int,
     *     goldBase: int,
     *     goldPerLevel: int,
     *     diamonds: int,
     *     titleCode: string|null
     * }>
     */
    public static function definitions(): array
    {
        return [
            [
                'code' => 'bestiary_trophy_25',
                'targetCount' => 13,
                'goldBase' => 200,
                'goldPerLevel' => 20,
                'diamonds' => 1,
                'titleCode' => null,
            ],
            [
                'code' => 'bestiary_trophy_50',
                'targetCount' => 25,
                'goldBase' => 400,
                'goldPerLevel' => 25,
                'diamonds' => 2,
                'titleCode' => null,
            ],
            [
                'code' => 'bestiary_trophy_75',
                'targetCount' => 38,
                'goldBase' => 700,
                'goldPerLevel' => 30,
                'diamonds' => 3,
                'titleCode' => null,
            ],
            [
                'code' => 'bestiary_trophy_100',
                'targetCount' => 50,
                'goldBase' => 1200,
                'goldPerLevel' => 40,
                'diamonds' => 5,
                'titleCode' => 'bestiary_archivist',
            ],
        ];
    }

    /**
     * @return array{
     *     code: string,
     *     targetCount: int,
     *     goldBase: int,
     *     goldPerLevel: int,
     *     diamonds: int,
     *     titleCode: string|null
     * }|null
     */
    public static function find(string $code): ?array
    {
        foreach (self::definitions() as $def) {
            if ($def['code'] === $code) {
                return $def;
            }
        }

        return null;
    }

    /**
     * @return array{gold: int, diamonds: int}
     */
    public static function claimReward(array $def, int $level): array
    {
        $level = max(1, $level);

        return [
            'gold' => $def['goldBase'] + $level * $def['goldPerLevel'],
            'diamonds' => $def['diamonds'],
        ];
    }
}
