<?php

declare(strict_types=1);

namespace App\Dto\Ship;

use Symfony\Component\Validator\Constraints as Assert;

final class ChangeMemberRoleInput
{
    #[Assert\NotBlank(message: 'User ID is required')]
    #[Assert\Uuid(message: 'Invalid user ID')]
    public ?string $userId = null;

    #[Assert\NotBlank(message: 'Role is required')]
    #[Assert\Choice(choices: ['OWNER', 'MANAGER', 'MEMBER'], message: 'Invalid role. Must be: OWNER, MANAGER, or MEMBER')]
    public string $role = '';
}
