<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\Exception\InvalidUserData;
use App\Identity\Domain\Model\Email;
use App\Identity\Domain\Model\HashedPassword;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<SecurityUser>
 */
final readonly class SecurityUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(private UserRepository $users)
    {
    }

    public function loadUserByIdentifier(string $identifier): SecurityUser
    {
        return SecurityUser::fromUser($this->domainUser($identifier));
    }

    public function refreshUser(UserInterface $user): SecurityUser
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class || is_subclass_of($class, SecurityUser::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $domainUser = $this->domainUser($user->getUserIdentifier());
        $domainUser->changePassword(new HashedPassword($newHashedPassword));
        $this->users->save($domainUser);
    }

    private function domainUser(string $email): User
    {
        try {
            $user = $this->users->ofEmail(new Email($email));
        } catch (InvalidUserData) {
            $user = null;
        }

        if (null === $user) {
            $exception = new UserNotFoundException();
            $exception->setUserIdentifier($email);

            throw $exception;
        }

        return $user;
    }
}
