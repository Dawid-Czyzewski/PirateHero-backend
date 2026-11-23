<?php

declare(strict_types=1);

namespace App\Combat\Presentation\Api;

use App\Combat\Application\Command\FightNpc\FightNpcCommand;
use Symfony\Component\Validator\Constraints as Assert;

final class FightNpcInput
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 40)]
    #[Assert\Regex('/^[a-z]+(-[a-z]+)*$/')]
    public ?string $npcId = null;

    public function toCommand(string $userId): FightNpcCommand
    {
        return new FightNpcCommand($userId, $this->npcId ?? throw new \LogicException('Input must be validated before use.'));
    }
}
