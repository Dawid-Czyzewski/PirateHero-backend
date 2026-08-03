<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Economy;

use App\Entity\ItemStatistics;
use App\Entity\Level;
use App\Entity\User;
use App\Entity\WearableItem;
use App\Entity\WearableItemTemplate;
use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Repository\WearableItemTemplateRepository;
use App\Service\Economy\WearableRewardFactory;
use App\Service\GameShop\WearableItemTemplateService;
use App\Service\GameShop\WearableTemplatePicker;
use App\Service\GameShop\WearableVariantResolver;
use App\Service\Progression\QuestProgressService;
use App\Tests\Support\UnconstructedInstance;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class WearableRewardFactoryTest extends TestCase
{
    public function testToClientPayloadShape(): void
    {
        $stats = (new ItemStatistics())
            ->setStrongPoints(5)
            ->setAgilityPoints(3)
            ->setIntelligencePoints(1)
            ->setCriticalChancePoints(2)
            ->setHealthPoints(20);

        $item = (new WearableItem())
            ->setName('Test Helm')
            ->setNameKey('helm.test')
            ->setType(WearableItemType::Helmet)
            ->setRarity(WearableItemRarity::RARE)
            ->setPrice(120)
            ->setImageKey('helm_rare')
            ->setStatistics($stats);

        $factory = new WearableRewardFactory(
            $this->createMock(EntityManagerInterface::class),
            UnconstructedInstance::of(WearableItemTemplateService::class),
            UnconstructedInstance::of(QuestProgressService::class),
        );

        $payload = $factory->toClientPayload($item);

        self::assertArrayHasKey('id', $payload);
        self::assertSame('Test Helm', $payload['name']);
        self::assertSame('helm.test', $payload['nameKey']);
        self::assertSame('helmet', $payload['type']);
        self::assertSame('RARE', $payload['rarity']);
        self::assertSame(120, $payload['price']);
        self::assertSame('helm_rare', $payload['imageKey']);
        self::assertIsArray($payload['statistics']);
    }

    public function testCreateForUserPersistsStatsAndItem(): void
    {
        $user = (new User())
            ->setEmail(sprintf('wrf_%s@test.local', bin2hex(random_bytes(3))))
            ->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))))
            ->setPassword('x')
            ->setLevel((new Level())->setName('2')->setExpToNextLevel(100));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(2))->method('persist');

        $template = new WearableItemTemplate();
        $template->setType(WearableItemType::Helmet);
        $template->setNameKey('helm.test');
        $template->setImageKey('helm_rare');
        $template->setRarity(WearableItemRarity::RARE);
        $template->setMinLevel(1);
        $template->setMaxLevel(99);

        $templateRepository = $this->createMock(WearableItemTemplateRepository::class);
        $templateRepository->method('findAvailableForTypeAndLevel')->willReturn([$template]);

        $templateService = new WearableItemTemplateService(
            $em,
            $templateRepository,
            new WearableVariantResolver($templateRepository, $this->createMock(LoggerInterface::class)),
            new WearableTemplatePicker($templateRepository),
        );

        $factory = new WearableRewardFactory(
            $em,
            $templateService,
            UnconstructedInstance::of(QuestProgressService::class),
        );

        $item = $factory->createForUser($user, WearableItemRarity::RARE);

        self::assertInstanceOf(WearableItem::class, $item);
        self::assertSame(WearableItemRarity::RARE, $item->getRarity());
        self::assertNotNull($item->getStatistics());
    }
}
