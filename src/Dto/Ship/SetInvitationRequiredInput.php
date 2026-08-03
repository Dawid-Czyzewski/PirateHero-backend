<?php

declare(strict_types=1);

namespace App\Dto\Ship;

use Symfony\Component\Validator\Constraints as Assert;

final class SetInvitationRequiredInput
{
    #[Assert\NotNull(message: 'shipId is required')]
    #[Assert\Positive]
    public ?int $shipId = null;

    #[Assert\NotNull(message: 'requiresInvitation is required')]
    public ?bool $requiresInvitation = null;
}
