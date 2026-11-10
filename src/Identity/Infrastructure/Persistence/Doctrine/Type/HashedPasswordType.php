<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine\Type;

use App\Identity\Domain\Model\HashedPassword;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

final class HashedPasswordType extends StringType
{
    public const NAME = 'hashed_password';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return match (true) {
            null === $value => null,
            $value instanceof HashedPassword => $value->toString(),
            default => throw InvalidType::new($value, self::NAME, ['null', HashedPassword::class]),
        };
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?HashedPassword
    {
        return null === $value || $value instanceof HashedPassword ? $value : new HashedPassword((string) $value);
    }
}
