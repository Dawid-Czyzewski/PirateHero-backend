<?php

declare(strict_types=1);

namespace App\Tests\Support;

final class EntityIdSetter
{
    public static function set(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setAccessible(true);
        $property->setValue($entity, $id);
    }
}
