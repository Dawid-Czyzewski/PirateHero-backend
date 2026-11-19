<?php

declare(strict_types=1);

namespace App\Character\Domain\Model;

enum Stat: string
{
    case Strength = 'strength';
    case Endurance = 'endurance';
    case Technique = 'technique';
    case Charisma = 'charisma';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $stat): string => $stat->value, self::cases());
    }
}
