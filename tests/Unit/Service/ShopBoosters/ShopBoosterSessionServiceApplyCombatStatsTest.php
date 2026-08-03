<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\ShopBoosters;

use App\Repository\ShopBoosterRepository;
use App\Repository\UserShopBoosterSessionRepository;
use App\Service\Economy\BoosterService;
use App\Service\ShopBoosters\ShopBoosterEffectParser;
use App\Service\ShopBoosters\ShopBoosterSessionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ShopBoosterSessionServiceApplyCombatStatsTest extends TestCase
{
    public function testApplySkillsBoosterScalesCoreStatsAndSyncsCriticalWithLuck(): void
    {
        $service = new ShopBoosterSessionService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ShopBoosterRepository::class),
            $this->createMock(UserShopBoosterSessionRepository::class),
            $this->createMock(BoosterService::class),
            new ShopBoosterEffectParser(),
        );

        $base = [
            'health' => 100,
            'strength' => 50,
            'agility' => 40,
            'luck' => 30,
            'intelligence' => 20,
            'critical' => 99,
        ];

        $out = $service->applySkillsBoosterToCombatStats($base, 0.1);

        self::assertSame(110, $out['health']);
        self::assertSame(55, $out['strength']);
        self::assertSame(44, $out['agility']);
        self::assertSame(33, $out['luck']);
        self::assertSame(22, $out['intelligence']);
        self::assertSame(33, $out['critical']);
    }
}
