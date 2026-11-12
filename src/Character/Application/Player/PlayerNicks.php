<?php

declare(strict_types=1);

namespace App\Character\Application\Player;

use App\Shared\Domain\Model\UserId;

interface PlayerNicks
{
    public function nickOf(UserId $userId): string;
}
