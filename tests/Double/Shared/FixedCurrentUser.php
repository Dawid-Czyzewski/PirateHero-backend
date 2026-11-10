<?php

declare(strict_types=1);

namespace App\Tests\Double\Shared;

use App\Shared\Application\Security\CurrentUser;
use App\Shared\Domain\Model\UserId;

final readonly class FixedCurrentUser implements CurrentUser
{
    public function __construct(private ?UserId $userId)
    {
    }

    public function userId(): ?UserId
    {
        return $this->userId;
    }
}
