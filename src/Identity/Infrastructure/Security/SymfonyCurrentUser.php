<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Shared\Application\Security\CurrentUser;
use App\Shared\Domain\Model\UserId;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class SymfonyCurrentUser implements CurrentUser
{
    public function __construct(private Security $security)
    {
    }

    public function userId(): ?UserId
    {
        $user = $this->security->getUser();

        return $user instanceof SecurityUser ? UserId::fromString($user->getId()) : null;
    }
}
