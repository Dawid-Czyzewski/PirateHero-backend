<?php

declare(strict_types=1);

namespace App\Character\Infrastructure\Identity;

use App\Character\Application\Player\PlayerNicks;
use App\Identity\Application\Query\GetUser\GetUserHandler;
use App\Identity\Application\Query\GetUser\GetUserQuery;
use App\Shared\Domain\Model\UserId;

final readonly class IdentityPlayerNicks implements PlayerNicks
{
    public function __construct(private GetUserHandler $getUser)
    {
    }

    public function nickOf(UserId $userId): string
    {
        return ($this->getUser)(new GetUserQuery($userId->toString()))->nick;
    }
}
