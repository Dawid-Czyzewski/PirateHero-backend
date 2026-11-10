<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure;

use App\Identity\Infrastructure\Security\SecurityUser;
use App\Identity\Infrastructure\Security\SecurityUserProvider;
use App\Tests\Double\Identity\InMemoryUserRepository;
use App\Tests\Double\Identity\UserMother;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

final class SecurityUserProviderTest extends TestCase
{
    private InMemoryUserRepository $users;
    private SecurityUserProvider $provider;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->users->save(UserMother::create('gracz@example.com', 'Cebulak'));
        $this->provider = new SecurityUserProvider($this->users);
    }

    public function testLoadsSecurityUserByEmail(): void
    {
        $user = $this->provider->loadUserByIdentifier('Gracz@Example.com');

        self::assertSame(UserMother::ID, $user->getId());
        self::assertSame('gracz@example.com', $user->getUserIdentifier());
        self::assertSame('hashed:tajnehaslo', $user->getPassword());
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unknownIdentifiers(): iterable
    {
        yield 'unknown email' => ['nikt@example.com'];
        yield 'not an email' => ['cebulak'];
    }

    #[DataProvider('unknownIdentifiers')]
    public function testThrowsUserNotFound(string $identifier): void
    {
        $this->expectException(UserNotFoundException::class);

        $this->provider->loadUserByIdentifier($identifier);
    }

    public function testUpgradePasswordStoresNewHashInDomainUser(): void
    {
        $this->provider->upgradePassword($this->provider->loadUserByIdentifier('gracz@example.com'), 'new-hash');

        self::assertSame('new-hash', $this->users->ofId(UserMother::create()->id())?->password()->toString());
    }

    public function testSupportsOnlySecurityUser(): void
    {
        self::assertTrue($this->provider->supportsClass(SecurityUser::class));
        self::assertFalse($this->provider->supportsClass(\stdClass::class));
    }
}
