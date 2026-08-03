<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Entity\WearableItem;
use App\Enum\QuestRewardType;

final readonly class QuestRewardApplicationResult
{
    /**
     * @param array{levelUp: bool, ...}|null $levelUpData
     */
    public function __construct(
        public QuestRewardType $rewardType,
        public int $rewardAmount,
        public ?array $levelUpData,
        public ?WearableItem $randomItem,
    ) {
    }
}
