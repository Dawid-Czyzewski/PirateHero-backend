<?php

declare(strict_types=1);

namespace App\Tests\Support;

final class UnconstructedInstance
{
    public static function of(string $className): object
    {
        return (new \ReflectionClass($className))->newInstanceWithoutConstructor();
    }
}
