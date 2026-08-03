<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Service\Progression\QuestTemplateDefaults;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class QuestTemplateFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach (QuestTemplateDefaults::createActiveTemplates() as $quest) {
            $manager->persist($quest);
        }

        $manager->flush();
    }
}
