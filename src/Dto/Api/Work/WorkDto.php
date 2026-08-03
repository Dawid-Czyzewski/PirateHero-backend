<?php

declare(strict_types=1);

namespace App\Dto\Api\Work;

final readonly class WorkDto
{
    public function __construct(
        public int $id,
        public string $title,
        public int $hoursCount,
        public int $baseGold,
        public int $effectiveBaseGold,
        public int $totalGoldAfterShip,
        public int $totalGoldPreview,
        public int $levelMultiplier,
        public int $bonusPercent,
        public int $shopBoosterPercent,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'hoursCount' => $this->hoursCount,
            'baseGold' => $this->baseGold,
            'effectiveBaseGold' => $this->effectiveBaseGold,
            'totalGoldAfterShip' => $this->totalGoldAfterShip,
            'totalGoldPreview' => $this->totalGoldPreview,
            'levelMultiplier' => $this->levelMultiplier,
            'bonusPercent' => $this->bonusPercent,
            'shopBoosterPercent' => $this->shopBoosterPercent,
        ];
    }
}
