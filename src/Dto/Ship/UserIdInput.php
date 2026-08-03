<?php

declare(strict_types=1);

namespace App\Dto\Ship;

use Symfony\Component\Validator\Constraints as Assert;

final class UserIdInput
{
    #[Assert\NotBlank(message: 'User ID is required')]
    #[Assert\Uuid(message: 'Invalid user ID')]
    public ?string $userId = null;
}
