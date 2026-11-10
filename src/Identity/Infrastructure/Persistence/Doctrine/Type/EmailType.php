<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine\Type;

use App\Identity\Domain\Model\Email;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

final class EmailType extends StringType
{
    public const NAME = 'email';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return match (true) {
            null === $value => null,
            $value instanceof Email => $value->toString(),
            default => throw InvalidType::new($value, self::NAME, ['null', Email::class]),
        };
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Email
    {
        return null === $value || $value instanceof Email ? $value : new Email((string) $value);
    }
}
