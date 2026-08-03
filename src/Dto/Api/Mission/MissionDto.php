<?php

declare(strict_types=1);

namespace App\Dto\Api\Mission;

final readonly class MissionDto
{
    public function __construct(
        public int $id,
        public string $title,
        public int $goldReward,
        public int $expReward,
        public int $baseGoldReward,
        public int $baseExpReward,
        public int $bonusPercent,
        public int $shopBoosterPercent,
        public int $durationInSeconds,
        public int $energyCost,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'goldReward' => $this->goldReward,
            'expReward' => $this->expReward,
            'baseGoldReward' => $this->baseGoldReward,
            'baseExpReward' => $this->baseExpReward,
            'bonusPercent' => $this->bonusPercent,
            'shopBoosterPercent' => $this->shopBoosterPercent,
            'durationInSeconds' => $this->durationInSeconds,
            'energyCost' => $this->energyCost,
        ];
    }
}
