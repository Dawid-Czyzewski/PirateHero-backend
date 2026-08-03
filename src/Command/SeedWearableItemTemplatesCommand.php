<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\GameShop\WearableItemTemplateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:wearable-item-templates:seed',
    description: 'Load wearable item catalog templates (category, image, level range) into wearable_item_template',
)]
final class SeedWearableItemTemplatesCommand extends Command
{
    public function __construct(
        private readonly WearableItemTemplateService $templateService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'purge',
            null,
            InputOption::VALUE_NONE,
            'Remove all rows from wearable_item_template before seeding'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $purge = (bool) $input->getOption('purge');

        if ($purge) {
            $io->note('Purging wearable_item_template…');
        }

        $result = $this->templateService->seedFromConfig($purge);

        $io->success(sprintf(
            'Templates seeded: %d created, %d updated (from WearableItemCatalog).',
            $result['created'],
            $result['updated']
        ));

        return Command::SUCCESS;
    }
}
