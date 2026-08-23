<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\ItemStatistics;
use PHPUnit\Framework\TestCase;

final class ItemStatisticsBumpTest extends TestCase
{
    public function testBumpPositiveStatsByOne(): void
    {
        $stats = new ItemStatistics();
        $stats->setStrongPoints(5);
        $stats->setAgilityPoints(0);
        $stats->setHealthPoints(3);
        $stats->setIntelligencePoints(0);
        $stats->setCriticalChancePoints(2);

        $stats->bumpPositiveStatsByOne();

        self::assertSame(6, $stats->getStrongPoints());
        self::assertSame(0, $stats->getAgilityPoints());
        self::assertSame(4, $stats->getHealthPoints());
        self::assertSame(0, $stats->getIntelligencePoints());
        self::assertSame(3, $stats->getCriticalChancePoints());
    }
}
