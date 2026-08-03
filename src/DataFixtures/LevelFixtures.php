<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Level;
use App\Progression\PlayerLevelTable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class LevelFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['core', 'levels', 'catalog'];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (PlayerLevelTable::rows() as $row) {
            $level = new Level();
            $level->setName($row['name']);
            $level->setExpToNextLevel($row['expToNextLevel']);
            $manager->persist($level);
        }

        $manager->flush();
    }
}
