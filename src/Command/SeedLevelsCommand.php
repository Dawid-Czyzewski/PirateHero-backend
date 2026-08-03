<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Level;
use App\Progression\PlayerLevelTable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed:levels',
    description: 'Upsert player levels 1–MAX from PlayerLevelTable',
)]
final class SeedLevelsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $repo = $this->entityManager->getRepository(Level::class);

        $created = 0;
        $updated = 0;

        foreach (PlayerLevelTable::rows() as $row) {
            $level = $repo->findOneBy(['name' => $row['name']]);
            if ($level === null) {
                $level = new Level();
                $level->setName($row['name']);
                ++$created;
            } else {
                ++$updated;
            }
            $level->setExpToNextLevel($row['expToNextLevel']);
            $this->entityManager->persist($level);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Levels: %d created, %d updated.', $created, $updated));

        return Command::SUCCESS;
    }
}
