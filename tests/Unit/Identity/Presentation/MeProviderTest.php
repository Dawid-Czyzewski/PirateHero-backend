<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Presentation;

use ApiPlatform\Metadata\Get;
use App\Identity\Application\Query\GetUser\GetUserHandler;
use App\Identity\Infrastructure\Security\SecurityUser;
use App\Identity\Presentation\Api\State\MeProvider;
use App\Tests\Double\Identity\InMemoryUserRepository;
use App\Tests\Double\Identity\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class MeProviderTest extends TestCase
{
    public function testReturnsLoggedInUser(): void
    {
        $user = UserMother::create('gracz@example.com', 'Cebulak');
        $users = new InMemoryUserRepository();
        $users->save($user);

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(SecurityUser::fromUser($user));

        $resource = (new MeProvider($security, new GetUserHandler($users)))->provide(new Get());

        self::assertSame(UserMother::ID, $resource->id);
        self::assertSame('Cebulak', $resource->nick);
    }

    public function testDeniesAccessWithoutUser(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $this->expectException(AccessDeniedException::class);
        (new MeProvider($security, new GetUserHandler(new InMemoryUserRepository())))->provide(new Get());
    }
}
