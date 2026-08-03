<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\ShopBoosters;

use App\Service\ShopBoosters\ShopBoosterPublicCodeResolve;
use PHPUnit\Framework\TestCase;

final class ShopBoosterPublicCodeResolveTest extends TestCase
{
    public function testNewIdIncludesLegacyAlias(): void
    {
        self::assertSame(['mis_3', 'm3'], ShopBoosterPublicCodeResolve::lookupCandidates('mis_3'));
    }

    public function testLegacyIdIncludesNewAlias(): void
    {
        self::assertContains('mis_2', ShopBoosterPublicCodeResolve::lookupCandidates('m2'));
        self::assertContains('m2', ShopBoosterPublicCodeResolve::lookupCandidates('m2'));
    }

    public function testUnknownCodeReturnsOnlyItself(): void
    {
        self::assertSame(['xyz'], ShopBoosterPublicCodeResolve::lookupCandidates('xyz'));
    }
}
