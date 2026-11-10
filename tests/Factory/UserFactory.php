<?php

declare(strict_types=1);

namespace App\Tests\Factory;

use App\Identity\Domain\Model\Email;
use App\Identity\Domain\Model\Nick;
use App\Identity\Domain\Model\PlainPassword;
use App\Identity\Domain\Model\User;
use App\Identity\Domain\Service\PasswordHasher;
use App\Shared\Domain\Model\UserId;
use Symfony\Component\Uid\Uuid;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public const DEFAULT_PASSWORD = 'password123';

    public function __construct(private readonly PasswordHasher $passwordHasher)
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'nick' => self::faker()->unique()->regexify('[a-z]{4,12}'),
            'password' => self::DEFAULT_PASSWORD,
        ];
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(fn (array $attributes): User => User::register(
            UserId::fromString(Uuid::v7()->toRfc4122()),
            new Email(self::string($attributes, 'email')),
            new Nick(self::string($attributes, 'nick')),
            $this->passwordHasher->hash(new PlainPassword(self::string($attributes, 'password'))),
            new \DateTimeImmutable(),
        ));
    }

    /**
     * @param array<mixed> $attributes
     */
    private static function string(array $attributes, string $key): string
    {
        $value = $attributes[$key] ?? null;
        \assert(\is_string($value));

        return $value;
    }
}
