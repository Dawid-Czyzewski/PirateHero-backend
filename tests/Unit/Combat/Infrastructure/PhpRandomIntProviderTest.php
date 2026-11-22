<?php

declare(strict_types=1);

namespace App\Tests\Unit\Combat\Infrastructure;

use App\Combat\Infrastructure\Random\PhpRandomIntProvider;
use PHPUnit\Framework\TestCase;

final class PhpRandomIntProviderTest extends TestCase
{
    public function testStaysWithinTheRequestedRange(): void
    {
        $random = new PhpRandomIntProvider();
        $seen = [];

        for ($i = 0; $i < 500; ++$i) {
            $value = $random->between(-2, 2);
            self::assertGreaterThanOrEqual(-2, $value);
            self::assertLessThanOrEqual(2, $value);
            $seen[$value] = true;
        }

        self::assertCount(5, $seen);
    }
}
