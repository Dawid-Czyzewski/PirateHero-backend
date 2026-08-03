<?php

declare(strict_types=1);

namespace App\Dto\Ship;

use Symfony\Component\Validator\Constraints as Assert;

final class DepositShipInput
{
    #[Assert\PositiveOrZero]
    public ?int $gold = null;

    #[Assert\PositiveOrZero]
    public ?int $diamonds = null;
}
