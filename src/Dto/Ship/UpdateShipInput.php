<?php

declare(strict_types=1);

namespace App\Dto\Ship;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateShipInput
{
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    public ?string $description = null;

    public ?string $internalNotes = null;
}
