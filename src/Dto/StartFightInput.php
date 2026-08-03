<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class StartFightInput
{
    #[Assert\NotBlank(message: 'Missing opponentId')]
    public ?string $opponentId = null;
}
