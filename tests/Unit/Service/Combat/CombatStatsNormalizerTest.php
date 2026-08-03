<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Combat;

use App\Service\Combat\CombatStatsNormalizer;
use PHPUnit\Framework\TestCase;

final class CombatStatsNormalizerTest extends TestCase
{
    public function testMapsLegacyCriticalToLuckWhenLuckMissing(): void
    {
        $out = CombatStatsNormalizer::forCombat([
            'strength' => 10,
            'critical' => 7,
        ]);

        self::assertSame(7, $out['luck']);
        self::assertSame(0, $out['intelligence']);
    }

    public function testPrefersExplicitLuckOverCritical(): void
    {
        $out = CombatStatsNormalizer::forCombat([
            'luck' => 12,
            'critical' => 99,
        ]);

        self::assertSame(12, $out['luck']);
    }
}
