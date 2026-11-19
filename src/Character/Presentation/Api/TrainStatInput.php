<?php

declare(strict_types=1);

namespace App\Character\Presentation\Api;

use App\Character\Application\Command\TrainStat\TrainStatCommand;
use App\Character\Domain\Model\Stat;
use Symfony\Component\Validator\Constraints as Assert;

final class TrainStatInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [Stat::class, 'values'])]
    public ?string $stat = null;

    public function toCommand(string $userId): TrainStatCommand
    {
        return new TrainStatCommand(
            $userId,
            Stat::from($this->stat ?? throw new \LogicException('Input must be validated before use.')),
        );
    }
}
