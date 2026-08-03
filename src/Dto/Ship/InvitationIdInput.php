<?php

declare(strict_types=1);

namespace App\Dto\Ship;

use Symfony\Component\Validator\Constraints as Assert;

final class InvitationIdInput
{
    #[Assert\NotNull(message: 'invitationId is required')]
    #[Assert\Positive]
    public ?int $invitationId = null;
}
