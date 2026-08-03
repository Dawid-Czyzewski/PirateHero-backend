<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Service\GameShop\WearableItemTemplateService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;


final class WearableItemTemplateFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly WearableItemTemplateService $templateService,
    ) {
    }

    public static function getGroups(): array
    {
        return ['core', 'items', 'catalog'];
    }

    public function load(ObjectManager $manager): void
    {
        $this->templateService->seedFromConfig(false);
    }
}
