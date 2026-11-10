<?php

declare(strict_types=1);

namespace App\Shared\Application\Security;

use App\Shared\Domain\Model\UserId;

interface CurrentUser
{
    public function userId(): ?UserId;
}
