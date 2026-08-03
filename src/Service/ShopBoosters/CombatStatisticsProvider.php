<?php

declare(strict_types=1);

namespace App\Service\ShopBoosters;

use App\Entity\User;

interface CombatStatisticsProvider
{
    public function pruneExpiredSessions(User $user): void;

    /**
     * @return array{strength: int, agility: int, health: int, intelligence: int, luck: int}
     */
    public function getCombatStatistics(User $user): array;
}
