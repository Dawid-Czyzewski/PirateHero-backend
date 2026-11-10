<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\GetUser;

use App\Identity\Domain\Exception\UserNotFound;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Domain\Model\UserId;

final readonly class GetUserHandler
{
    public function __construct(private UserRepository $users)
    {
    }

    /**
     * @throws UserNotFound
     */
    public function __invoke(GetUserQuery $query): UserView
    {
        $id = UserId::fromString($query->userId);
        $user = $this->users->ofId($id) ?? throw UserNotFound::withId($id);

        return UserView::fromUser($user);
    }
}
