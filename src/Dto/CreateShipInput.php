<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateShipInput
{
    #[Assert\NotBlank(message: 'Title is required')]
    #[Assert\Length(max: 255)]
    public string $title = '';

    public ?string $description = null;
}
