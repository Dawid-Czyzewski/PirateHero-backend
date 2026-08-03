<?php

declare(strict_types=1);

namespace App\Enum;

enum ShipRole: string
{
    case OWNER = 'OWNER';
    case MANAGER = 'MANAGER';
    case MEMBER = 'MEMBER';

    public static function priorityOrder(): array
    {
        return [
            self::OWNER,
            self::MANAGER,
            self::MEMBER,
        ];
    }
}
