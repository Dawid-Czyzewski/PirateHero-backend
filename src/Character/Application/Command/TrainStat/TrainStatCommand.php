<?php

declare(strict_types=1);

namespace App\Character\Application\Command\TrainStat;

use App\Character\Domain\Model\Stat;

final readonly class TrainStatCommand
{
    public function __construct(
        public string $userId,
        public Stat $stat,
    ) {
    }
}
