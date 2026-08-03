<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Entity\User;

final class UserLevelResolver
{
    public static function of(User $user): int
    {
        $level = $user->getLevel();

        return $level !== null ? max(1, (int) $level->getName()) : 1;
    }
}
