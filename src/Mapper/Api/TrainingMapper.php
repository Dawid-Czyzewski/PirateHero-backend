<?php

declare(strict_types=1);

namespace App\Mapper\Api;

use App\Dto\Api\Training\TrainingDto;
use App\Dto\Api\Training\TrainingListResponse;
use App\Entity\Training;

final readonly class TrainingMapper
{
    public static function fromTraining(Training $training): TrainingDto
    {
        return new TrainingDto(
            id: (int) $training->getId(),
            title: (string) $training->getTitle(),
            description: (string) $training->getDescription(),
            durationInSeconds: (int) $training->getDurationInSeconds(),
            trainingPointsCost: (int) $training->getTrainingPointsCost(),
            skillPointsReward: (int) $training->getSkillPointsReward(),
            statType: $training->getStatType()?->value,
        );
    }

    /**
     * @param list<TrainingDto> $trainings
     */
    public static function listResponse(array $trainings): TrainingListResponse
    {
        return new TrainingListResponse($trainings);
    }
}
