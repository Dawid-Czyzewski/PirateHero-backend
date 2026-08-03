<?php

declare(strict_types=1);

namespace App\Dto\Ship;

use Symfony\Component\Validator\Constraints as Assert;

final class InviteMemberInput
{
    #[Assert\NotBlank(message: 'Username is required')]
    public string $username = '';
}
