<?php

declare(strict_types=1);

namespace App\Application\Command\User;

final readonly class RegisterUserCommand
{
    public function __construct(
        public string $email,
        public string $password,
        public string $username,
        public string $avatarName,
        public string $frontendUrl,
    ) {
    }
}
