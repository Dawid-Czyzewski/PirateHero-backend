<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\GameShop;

use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Repository\WearableItemTemplateRepository;
use App\Service\GameShop\WearableVariantResolver;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class WearableVariantResolverTest extends TestCase
{
    public function testPickRandomVariantAtHighLevelDoesNotThrow(): void
    {
        $repository = $this->createMock(WearableItemTemplateRepository::class);
        $repository->method('findAvailableForTypeAndLevel')->willReturn([]);

        $resolver = new WearableVariantResolver($repository, new NullLogger());
        $pick = $resolver->pickRandomVariant(WearableItemType::Helmet, 15, WearableItemRarity::RARE, 'loot');

        self::assertArrayHasKey('nameKey', $pick);
        self::assertArrayHasKey('imageKey', $pick);
        self::assertArrayHasKey('rarity', $pick);
    }

    public function testResolveFallsBackToMaxTemplateLevelForVeryHighPlayer(): void
    {
        $repository = $this->createMock(WearableItemTemplateRepository::class);
        $repository->method('findAvailableForTypeAndLevel')->willReturn([]);

        $resolver = new WearableVariantResolver($repository, new NullLogger());
        $resolved = $resolver->resolve(WearableItemType::Boots, 90, 'shop');

        self::assertNotEmpty($resolved['variants']);
        self::assertSame(75, $resolved['fallbackLevel']);
        self::assertSame(75, $resolved['effectiveLevel']);
    }

    public function testResolveAtLevelSixtyUsesTierSixCatalogWithoutFallback(): void
    {
        $repository = $this->createMock(WearableItemTemplateRepository::class);
        $repository->method('findAvailableForTypeAndLevel')->willReturn([]);

        $resolver = new WearableVariantResolver($repository, new NullLogger());
        $resolved = $resolver->resolve(WearableItemType::Boots, 60, 'shop');

        self::assertNotEmpty($resolved['variants']);
        self::assertNull($resolved['fallbackLevel']);
        self::assertSame(60, $resolved['effectiveLevel']);
    }

    public function testResolveAtLevelFortyUsesTierFiveCatalogWithoutFallback(): void
    {
        $repository = $this->createMock(WearableItemTemplateRepository::class);
        $repository->method('findAvailableForTypeAndLevel')->willReturn([]);

        $resolver = new WearableVariantResolver($repository, new NullLogger());
        $resolved = $resolver->resolve(WearableItemType::Boots, 40, 'shop');

        self::assertNotEmpty($resolved['variants']);
        self::assertNull($resolved['fallbackLevel']);
        self::assertSame(40, $resolved['effectiveLevel']);
    }

    public function testResolveAtLevelThirtyUsesTierFourCatalogWithoutFallback(): void
    {
        $repository = $this->createMock(WearableItemTemplateRepository::class);
        $repository->method('findAvailableForTypeAndLevel')->willReturn([]);

        $resolver = new WearableVariantResolver($repository, new NullLogger());
        $resolved = $resolver->resolve(WearableItemType::Weapon, 30, 'shop');

        self::assertNotEmpty($resolved['variants']);
        self::assertNull($resolved['fallbackLevel']);
        self::assertSame(30, $resolved['effectiveLevel']);
    }

    public function testResolveAtLevelThirtyFiveUsesTierFourCatalogWithoutFallback(): void
    {
        $repository = $this->createMock(WearableItemTemplateRepository::class);
        $repository->method('findAvailableForTypeAndLevel')->willReturn([]);

        $resolver = new WearableVariantResolver($repository, new NullLogger());
        $resolved = $resolver->resolve(WearableItemType::Helmet, 35, 'shop');

        self::assertNotEmpty($resolved['variants']);
        self::assertNull($resolved['fallbackLevel']);
        self::assertSame(35, $resolved['effectiveLevel']);
    }

    public function testResolveAtLevelTwentyUsesTierThreeCatalogWithoutFallback(): void
    {
        $repository = $this->createMock(WearableItemTemplateRepository::class);
        $repository->method('findAvailableForTypeAndLevel')->willReturn([]);

        $resolver = new WearableVariantResolver($repository, new NullLogger());
        $resolved = $resolver->resolve(WearableItemType::Boots, 20, 'shop');

        self::assertNotEmpty($resolved['variants']);
        self::assertNull($resolved['fallbackLevel']);
        self::assertSame(20, $resolved['effectiveLevel']);
    }
}
