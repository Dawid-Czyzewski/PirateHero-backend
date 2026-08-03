<?php

declare(strict_types=1);

namespace App\Tests\Unit\Progression;

use App\Progression\LevelRankTitleCatalog;
use PHPUnit\Framework\TestCase;

final class LevelRankTitleCatalogTest extends TestCase
{
    public function testEveryFiveLevelsFromFiveToOneHundred(): void
    {
        $defs = LevelRankTitleCatalog::definitions();
        self::assertCount(20, $defs);
        self::assertSame(20, LevelRankTitleCatalog::count());
        self::assertSame('lvl_rank_5', $defs[0]['code']);
        self::assertSame(5, $defs[0]['level']);
        self::assertSame('lvl_rank_100', $defs[19]['code']);
        self::assertSame(100, $defs[19]['level']);
    }
}
