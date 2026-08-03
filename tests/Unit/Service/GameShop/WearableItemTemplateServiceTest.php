<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\GameShop;

use App\Entity\WearableItem;
use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Repository\WearableItemTemplateRepository;
use App\Service\GameShop\WearableItemTemplateService;
use App\Service\GameShop\WearableVariantResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class WearableItemTemplateServiceTest extends TestCase
{
    public function testApplyRandomTemplateAtLevelFifteenDoesNotThrow(): void
    {
        $repo = $this->createMock(WearableItemTemplateRepository::class);
        $repo->method('findAvailableForTypeAndLevel')->willReturn([]);

        $resolver = new WearableVariantResolver($repo, new NullLogger());
        $em = $this->createMock(EntityManagerInterface::class);
        $picker = new \App\Service\GameShop\WearableTemplatePicker($repo);

        $service = new WearableItemTemplateService($em, $repo, $resolver, $picker);

        $item = new WearableItem();
        $item->setType(WearableItemType::Helmet);
        $item->setRarity(WearableItemRarity::RARE);

        $service->applyRandomTemplate($item, 15, WearableItemType::Helmet, WearableItemRarity::RARE);

        self::assertNotEmpty($item->getNameKey());
        self::assertNotEmpty($item->getImageKey());
    }
}
