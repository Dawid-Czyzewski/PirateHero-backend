<?php

declare(strict_types=1);

namespace App\Dto\Ship;

use Symfony\Component\Validator\Constraints as Assert;

final class ShipIdInput
{
    #[Assert\NotNull(message: 'shipId is required')]
    #[Assert\Positive]
    public ?int $shipId = null;
}
