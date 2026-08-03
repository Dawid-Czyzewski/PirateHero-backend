<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\ShopBooster;
use App\Service\ShopBoosters\ShopBoosterCatalogDefinition;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class ShopBoosterFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['catalog'];
    }

    public function load(ObjectManager $manager): void
    {
        if ($manager->getRepository(ShopBooster::class)->count([]) > 0) {
            return;
        }

        foreach (ShopBoosterCatalogDefinition::rows() as $row) {
            $entity = new ShopBooster();
            $entity->setPublicCode($row['publicCode']);
            $entity->setCategory($row['category']);
            $entity->setCurrency($row['currency']);
            $entity->setPrice($row['price']);
            $entity->setDurationHours($row['durationHours']);
            $entity->setName($row['name']);
            $entity->setDescription($row['description']);
            $entity->setEffect($row['effect']);
            $entity->setSortOrder($row['sortOrder']);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
