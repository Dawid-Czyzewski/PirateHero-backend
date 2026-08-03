<?php

declare(strict_types=1);

namespace App\Dto\Api\Training;

final readonly class TrainingListResponse
{
    /**
     * @param list<TrainingDto> $trainings
     */
    public function __construct(
        public array $trainings,
    ) {
    }

    public function toArray(): array
    {
        return [
            'trainings' => array_map(static fn (TrainingDto $t) => $t->toArray(), $this->trainings),
        ];
    }
}
