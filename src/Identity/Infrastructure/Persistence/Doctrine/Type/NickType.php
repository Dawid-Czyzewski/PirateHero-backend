<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Persistence\Doctrine\Type;

use App\Identity\Domain\Model\Nick;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\StringType;

final class NickType extends StringType
{
    public const NAME = 'nick';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return match (true) {
            null === $value => null,
            $value instanceof Nick => $value->toString(),
            default => throw InvalidType::new($value, self::NAME, ['null', Nick::class]),
        };
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Nick
    {
        return null === $value || $value instanceof Nick ? $value : new Nick((string) $value);
    }
}
