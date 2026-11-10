<?php

declare(strict_types=1);

namespace App\Identity\Domain\Model;

use App\Shared\Domain\Model\UserId;

class User
{
    public const ROLE_USER = 'ROLE_USER';

    /**
     * @param list<string> $roles
     */
    private function __construct(
        private readonly UserId $id,
        private Email $email,
        private Nick $nick,
        private HashedPassword $password,
        private array $roles,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public static function register(
        UserId $id,
        Email $email,
        Nick $nick,
        HashedPassword $password,
        \DateTimeImmutable $registeredAt,
    ): self {
        return new self($id, $email, $nick, $password, [], $registeredAt);
    }

    public function id(): UserId
    {
        return $this->id;
    }

    public function email(): Email
    {
        return $this->email;
    }

    public function nick(): Nick
    {
        return $this->nick;
    }

    public function password(): HashedPassword
    {
        return $this->password;
    }

    /**
     * @return list<string>
     */
    public function roles(): array
    {
        return array_values(array_unique([...$this->roles, self::ROLE_USER]));
    }

    public function grantRole(string $role): void
    {
        if (!\in_array($role, $this->roles(), true)) {
            $this->roles[] = $role;
        }
    }

    public function changePassword(HashedPassword $password): void
    {
        $this->password = $password;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
