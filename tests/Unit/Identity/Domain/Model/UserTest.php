<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Domain\Model;

use App\Identity\Domain\Model\HashedPassword;
use App\Identity\Domain\Model\User;
use App\Tests\Double\Identity\UserMother;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testRegisterSetsAllData(): void
    {
        $user = UserMother::create('gracz@example.com', 'Cebulak');

        self::assertSame(UserMother::ID, $user->id()->toString());
        self::assertSame('gracz@example.com', $user->email()->toString());
        self::assertSame('Cebulak', $user->nick()->toString());
        self::assertSame('hashed:tajnehaslo', $user->password()->toString());
        self::assertEquals(new \DateTimeImmutable('2026-11-03 12:00:00'), $user->createdAt());
    }

    public function testNewUserHasOnlyRoleUser(): void
    {
        self::assertSame([User::ROLE_USER], UserMother::create()->roles());
    }

    public function testGrantRoleAddsRoleOnce(): void
    {
        $user = UserMother::create();

        $user->grantRole('ROLE_ADMIN');
        $user->grantRole('ROLE_ADMIN');
        $user->grantRole(User::ROLE_USER);

        self::assertSame(['ROLE_ADMIN', User::ROLE_USER], $user->roles());
    }

    public function testChangePasswordReplacesHash(): void
    {
        $user = UserMother::create();

        $user->changePassword(new HashedPassword('hashed:nowehaslo'));

        self::assertSame('hashed:nowehaslo', $user->password()->toString());
    }
}
