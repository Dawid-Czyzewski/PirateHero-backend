<?php

declare(strict_types=1);

namespace App\Dto\Api\Work;

final readonly class WorkCompleteResponse
{
    /**
     * @param list<WorkDto> $works
     */
    public function __construct(
        public int $earnedGold,
        public int $bonusPercent,
        public array $works,
    ) {
    }

    public function toArray(): array
    {
        return [
            'earnedGold' => $this->earnedGold,
            'bonusPercent' => $this->bonusPercent,
            'works' => array_map(static fn (WorkDto $w) => $w->toArray(), $this->works),
        ];
    }
}
