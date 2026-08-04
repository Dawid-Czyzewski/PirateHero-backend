<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\PlayerTitle;
use App\Progression\PlayerTitleCatalog;
use App\Repository\PlayerTitleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed:player-titles',
    description: 'Upsert player_title catalog (safe when table was wiped)',
)]
final class SeedPlayerTitlesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerTitleRepository $playerTitleRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $created = 0;
        $updated = 0;

        foreach (PlayerTitleCatalog::definitions() as $def) {
            $title = $this->playerTitleRepository->findOneByCode($def['code']);
            if ($title === null) {
                $title = new PlayerTitle();
                $title->setCode($def['code']);
                ++$created;
            } else {
                ++$updated;
            }

            $title->setNameKey('titles.'.$def['code'].'.name');
            $title->setDescriptionKey('titles.'.$def['code'].'.unlockHint');
            $title->setUnlockType($def['unlockType']);
            $title->setUnlockValue($def['unlockValue']);
            $title->setUnlockDungeonId($def['unlockDungeonId']);
            $title->setSortOrder($def['sortOrder']);
            $this->entityManager->persist($title);
        }

        $this->entityManager->flush();
        $io->success(sprintf('Player titles: %d created, %d updated.', $created, $updated));

        return Command::SUCCESS;
    }
}
