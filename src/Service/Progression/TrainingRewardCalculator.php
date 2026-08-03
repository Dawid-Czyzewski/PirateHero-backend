<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Entity\Training;
use App\Entity\UserBaseStatistics;
use App\Enum\UserStatType;
use App\Exception\ResourceNotFoundException;

final class TrainingRewardCalculator
{
    /**
     * @return array{statType: string|null, skillPoints: int}
     */
    public function apply(UserBaseStatistics $stats, Training $training): array
    {
        $reward = (int) $training->getSkillPointsReward();
        $statType = $training->getStatType();
        if ($statType === null) {
            return ['statType' => null, 'skillPoints' => $reward];
        }

        match ($statType) {
            UserStatType::STRENGTH => $stats->setStrength((int) ($stats->getStrength() ?? 0) + $reward),
            UserStatType::AGILITY => $stats->setAgility((int) ($stats->getAgility() ?? 0) + $reward),
            UserStatType::INTELLIGENCE => $stats->setIntelligence((int) ($stats->getIntelligence() ?? 0) + $reward),
            UserStatType::ENDURANCE => $stats->setEndurance((int) ($stats->getEndurance() ?? 0) + $reward),
            UserStatType::LUCK => $stats->setLuck((int) ($stats->getLuck() ?? 0) + $reward),
        };

        return ['statType' => $statType->value, 'skillPoints' => $reward];
    }

    public function requireBaseStatistics(\App\Entity\User $user): UserBaseStatistics
    {
        $stats = $user->getUserBaseStatistics();
        if ($stats === null) {
            throw new ResourceNotFoundException('userBaseStatisticsNotFound');
        }

        return $stats;
    }
}
