<?php

declare(strict_types=1);

namespace App\Dto\Api\Training;

final readonly class TrainingDto
{
    public function __construct(
        public int $id,
        public string $title,
        public string $description,
        public int $durationInSeconds,
        public int $trainingPointsCost,
        public int $skillPointsReward,
        public ?string $statType,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'durationInSeconds' => $this->durationInSeconds,
            'trainingPointsCost' => $this->trainingPointsCost,
            'skillPointsReward' => $this->skillPointsReward,
            'statType' => $this->statType,
        ];
    }
}
