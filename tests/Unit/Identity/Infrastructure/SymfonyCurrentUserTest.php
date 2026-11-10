<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\Identity\Infrastructure\Security\SymfonyCurrentUser;
use App\Tests\Double\Identity\UserMother;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\InMemoryUser;

final class SymfonyCurrentUserTest extends TestCase
{
    public function testReturnsIdOfAuthenticatedUser(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(SecurityUser::fromUser(UserMother::create()));

        self::assertSame(UserMother::ID, (new SymfonyCurrentUser($security))->userId()?->toString());
    }

    public function testReturnsNullForAnonymous(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        self::assertNull((new SymfonyCurrentUser($security))->userId());
    }

    public function testReturnsNullForForeignUserType(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(new InMemoryUser('admin', null));

        self::assertNull((new SymfonyCurrentUser($security))->userId());
    }
}
