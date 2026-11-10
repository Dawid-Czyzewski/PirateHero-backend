<?php

declare(strict_types=1);

namespace App\Tests\Double\Identity;

use App\Identity\Domain\Model\Email;
use App\Identity\Domain\Model\Nick;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Repository\UserRepository;
use App\Shared\Domain\Model\UserId;
use Symfony\Component\Uid\Uuid;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $users = [];

    public function nextIdentity(): UserId
    {
        return UserId::fromString(Uuid::v7()->toRfc4122());
    }

    public function save(User $user): void
    {
        $this->users[$user->id()->toString()] = $user;
    }

    public function ofId(UserId $id): ?User
    {
        return $this->users[$id->toString()] ?? null;
    }

    public function ofEmail(Email $email): ?User
    {
        return $this->find(static fn (User $user) => $user->email()->equals($email));
    }

    public function ofNick(Nick $nick): ?User
    {
        return $this->find(static fn (User $user) => $user->nick()->equals($nick));
    }

    public function count(): int
    {
        return \count($this->users);
    }

    /**
     * @param callable(User): bool $matches
     */
    private function find(callable $matches): ?User
    {
        foreach ($this->users as $user) {
            if ($matches($user)) {
                return $user;
            }
        }

        return null;
    }
}
