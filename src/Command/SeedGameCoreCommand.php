<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\GameShop\WearableItemTemplateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed:game-core',
    description: 'Seed levels 1–100 and wearable item templates (safe upsert, no DB wipe)',
)]
final class SeedGameCoreCommand extends Command
{
    public function __construct(
        private readonly WearableItemTemplateService $templateService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'purge-templates',
            null,
            InputOption::VALUE_NONE,
            'Delete all wearable_item_template rows before seeding catalog',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $levelsCmd = $this->getApplication()?->find('app:seed:levels');
        if ($levelsCmd === null) {
            $io->error('Command app:seed:levels not found.');

            return Command::FAILURE;
        }

        $levelsStatus = $levelsCmd->run(new ArrayInput([]), $output);
        if ($levelsStatus !== Command::SUCCESS) {
            return $levelsStatus;
        }

        $purge = (bool) $input->getOption('purge-templates');
        if ($purge) {
            $io->note('Purging wearable_item_template…');
        }

        $result = $this->templateService->seedFromConfig($purge);
        $io->success(sprintf(
            'Wearable templates: %d created, %d updated.',
            $result['created'],
            $result['updated'],
        ));

        return Command::SUCCESS;
    }
}
