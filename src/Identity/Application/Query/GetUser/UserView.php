<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\GetUser;

use App\Identity\Domain\Model\User;

final readonly class UserView
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        public string $id,
        public string $email,
        public string $nick,
        public array $roles,
        public \DateTimeImmutable $createdAt,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self(
            $user->id()->toString(),
            $user->email()->toString(),
            $user->nick()->toString(),
            $user->roles(),
            $user->createdAt(),
        );
    }
}
