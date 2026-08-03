<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\BoosterTemplate;
use App\Enum\BoosterType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class BoosterTemplateFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $boosterTypes = BoosterType::cases();

        foreach ($boosterTypes as $type) {
            $tier1 = new BoosterTemplate();
            $tier1->setName($this->getBoosterName($type, 1));
            $tier1->setType($type);
            $tier1->setEffectAmount($this->getEffectAmount($type, 1));
            $tier1->setDescription($this->getDescription($type, 1));
            $tier1->setTier(1);
            $manager->persist($tier1);

            $tier2 = new BoosterTemplate();
            $tier2->setName($this->getBoosterName($type, 2));
            $tier2->setType($type);
            $tier2->setEffectAmount($this->getEffectAmount($type, 2));
            $tier2->setDescription($this->getDescription($type, 2));
            $tier2->setTier(2);
            $manager->persist($tier2);

            $tier3 = new BoosterTemplate();
            $tier3->setName($this->getBoosterName($type, 3));
            $tier3->setType($type);
            $tier3->setEffectAmount($this->getEffectAmount($type, 3));
            $tier3->setDescription($this->getDescription($type, 3));
            $tier3->setTier(3);
            $manager->persist($tier3);
        }

        $manager->flush();
    }

    private function getBoosterName(BoosterType $type, int $tier): string
    {
        return "booster.{$type->value}.{$tier}.name";
    }

    private function getEffectAmount(BoosterType $type, int $tier): int
    {
        $baseAmounts = [
            BoosterType::ENERGY->value => [20, 50, 100],
            BoosterType::TRAINING_POINTS->value => [5, 15, 30],
            BoosterType::DUEL_POINTS->value => [5, 15, 30],
            BoosterType::SKILLS->value => [10, 20, 50],
        ];

        return $baseAmounts[$type->value][$tier - 1];
    }

    private function getDescription(BoosterType $type, int $tier): string
    {
        return "booster.{$type->value}.{$tier}.description";
    }
}
