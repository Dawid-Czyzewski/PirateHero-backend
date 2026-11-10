<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Application;

use App\Identity\Application\Query\GetUser\GetUserHandler;
use App\Identity\Application\Query\GetUser\GetUserQuery;
use App\Identity\Domain\Exception\UserNotFound;
use App\Tests\Double\Identity\InMemoryUserRepository;
use App\Tests\Double\Identity\UserMother;
use PHPUnit\Framework\TestCase;

final class GetUserHandlerTest extends TestCase
{
    public function testReturnsViewWithoutPassword(): void
    {
        $users = new InMemoryUserRepository();
        $users->save(UserMother::create('gracz@example.com', 'Cebulak'));

        $view = (new GetUserHandler($users))(new GetUserQuery(UserMother::ID));

        self::assertSame(UserMother::ID, $view->id);
        self::assertSame('gracz@example.com', $view->email);
        self::assertSame('Cebulak', $view->nick);
        self::assertSame(['ROLE_USER'], $view->roles);
        self::assertObjectNotHasProperty('password', $view);
    }

    public function testThrowsWhenUserDoesNotExist(): void
    {
        $this->expectException(UserNotFound::class);

        (new GetUserHandler(new InMemoryUserRepository()))(new GetUserQuery(UserMother::ID));
    }
}
