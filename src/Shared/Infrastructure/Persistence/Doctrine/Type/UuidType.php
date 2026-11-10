<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Domain\Model\Uuid;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Exception\InvalidType;
use Doctrine\DBAL\Types\GuidType;

abstract class UuidType extends GuidType
{
    /**
     * @return class-string<Uuid>
     */
    abstract protected function idClass(): string;

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        $class = $this->idClass();

        return match (true) {
            null === $value => null,
            $value instanceof $class => $value->toString(),
            default => throw InvalidType::new($value, static::class, ['null', $class]),
        };
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?Uuid
    {
        $class = $this->idClass();

        if (null === $value || $value instanceof $class) {
            return $value;
        }

        return $class::fromString((string) $value);
    }
}
