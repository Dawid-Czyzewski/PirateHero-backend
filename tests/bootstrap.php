<?php

declare(strict_types=1);

use App\Entity\Level;
use App\Kernel;
use App\Progression\PlayerLevelTable;
use Doctrine\ORM\Tools\SchemaTool;

require dirname(__DIR__).'/config/bootstrap.php';

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'test', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();
$container = $kernel->getContainer();
if ($container->has('doctrine')) {
    $entityManager = $container->get('doctrine')->getManager();
    $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
    if ($metadata !== []) {
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);

        foreach (PlayerLevelTable::rows() as $row) {
            $level = new Level();
            $level->setName($row['name']);
            $level->setExpToNextLevel($row['expToNextLevel']);
            $entityManager->persist($level);
        }
        $entityManager->flush();
    }
}
$kernel->shutdown();
