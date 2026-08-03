<?php

declare(strict_types=1);

namespace App\Dto\Ship;

use Symfony\Component\Validator\Constraints as Assert;

final class UpgradeShipInput
{
    #[Assert\NotBlank(message: 'upgradeType is required')]
    #[Assert\Choice(choices: ['skills', 'work', 'missions', 'hull'], message: 'Invalid upgrade type. Must be: skills, work, missions, or hull')]
    public string $upgradeType = '';
}
