<?php

declare(strict_types=1);

namespace App\Enum;

enum UserStatType: string
{
    case STRENGTH = 'STRENGTH';
    case AGILITY = 'AGILITY';
    case INTELLIGENCE = 'INTELLIGENCE';
    case ENDURANCE = 'ENDURANCE';
    case LUCK = 'LUCK';

    public static function fromRequest(string $raw): self
    {
        $key = strtoupper(trim($raw));
        if ($key === 'CRITICAL_CHANCE') {
            return self::INTELLIGENCE;
        }
        if ($key === 'HEALTH') {
            return self::ENDURANCE;
        }

        return self::from($key);
    }
}
