<?php

declare(strict_types=1);

namespace App\Combat\Domain\Model;

final readonly class Reward
{
    public function __construct(
        public int $experience,
        public int $onions,
    ) {
    }

    /**
     * @return array{experience: int, onions: int}
     */
    public function toArray(): array
    {
        return ['experience' => $this->experience, 'onions' => $this->onions];
    }
}
