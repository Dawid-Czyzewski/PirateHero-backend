<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\Model\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @param non-empty-string $email
     * @param list<string>     $roles
     */
    private function __construct(
        private string $id,
        private string $email,
        private string $password,
        private array $roles,
    ) {
    }

    public static function fromUser(User $user): self
    {
        $email = $user->email()->toString();
        \assert('' !== $email);

        return new self($user->id()->toString(), $email, $user->password()->toString(), $user->roles());
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }
}
