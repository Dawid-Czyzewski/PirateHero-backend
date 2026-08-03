<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Service\Progression\QuestTemplateDefaults;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class QuestTemplateFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['catalog'];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (QuestTemplateDefaults::createActiveTemplates() as $quest) {
            $manager->persist($quest);
        }

        $manager->flush();
    }
}
