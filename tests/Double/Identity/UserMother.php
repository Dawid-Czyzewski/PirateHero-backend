<?php

declare(strict_types=1);

namespace App\Tests\Double\Identity;

use App\Identity\Domain\Model\Email;
use App\Identity\Domain\Model\HashedPassword;
use App\Identity\Domain\Model\Nick;
use App\Identity\Domain\Model\User;
use App\Shared\Domain\Model\UserId;

final class UserMother
{
    public const ID = '01a1a31d-3df0-7194-80d3-f04533ffb060';

    public static function create(
        string $email = 'gracz@example.com',
        string $nick = 'Cebulak',
        string $id = self::ID,
    ): User {
        return User::register(
            UserId::fromString($id),
            new Email($email),
            new Nick($nick),
            new HashedPassword('hashed:tajnehaslo'),
            new \DateTimeImmutable('2026-11-03 12:00:00'),
        );
    }
}
