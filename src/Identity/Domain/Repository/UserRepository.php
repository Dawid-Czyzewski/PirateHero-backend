<?php

declare(strict_types=1);

namespace App\Identity\Domain\Repository;

use App\Identity\Domain\Exception\UserAlreadyExists;
use App\Identity\Domain\Model\Email;
use App\Identity\Domain\Model\Nick;
use App\Identity\Domain\Model\User;
use App\Shared\Domain\Model\UserId;

interface UserRepository
{
    public function nextIdentity(): UserId;

    /**
     * @throws UserAlreadyExists
     */
    public function save(User $user): void;

    public function ofId(UserId $id): ?User;

    public function ofEmail(Email $email): ?User;

    public function ofNick(Nick $nick): ?User;
}
