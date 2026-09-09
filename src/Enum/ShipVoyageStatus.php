<?php

declare(strict_types=1);

namespace App\Enum;

enum ShipVoyageStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
