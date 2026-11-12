<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Infrastructure;

use App\Character\Infrastructure\Identity\IdentityPlayerNicks;
use App\Identity\Application\Query\GetUser\GetUserHandler;
use App\Identity\Domain\Exception\UserNotFound;
use App\Shared\Domain\Model\UserId;
use App\Tests\Double\Identity\InMemoryUserRepository;
use App\Tests\Double\Identity\UserMother;
use PHPUnit\Framework\TestCase;

final class IdentityPlayerNicksTest extends TestCase
{
    public function testReturnsNickOfRegisteredUser(): void
    {
        $users = new InMemoryUserRepository();
        $users->save(UserMother::create(nick: 'Cebulak'));

        $nick = (new IdentityPlayerNicks(new GetUserHandler($users)))->nickOf(UserId::fromString(UserMother::ID));

        self::assertSame('Cebulak', $nick);
    }

    public function testFailsForUnknownUser(): void
    {
        $this->expectException(UserNotFound::class);

        (new IdentityPlayerNicks(new GetUserHandler(new InMemoryUserRepository())))->nickOf(UserId::fromString(UserMother::ID));
    }
}
