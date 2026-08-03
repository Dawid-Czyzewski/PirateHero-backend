<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Restores the free shop refresh flag for every user store.
 * Run once per day via cron (e.g. midnight Europe/Warsaw).
 *
 * Energy / fight / training paid-refill daily limits already reset lazily
 * on the next refill attempt when the calendar day changes — no cron needed.
 */
#[AsCommand(
    name: 'app:daily-reset:shop-refresh',
    description: 'Reset free shop refresh availability for all user stores',
)]
final class DailyResetShopRefreshCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $affected = $this->connection->executeStatement(
            'UPDATE user_store SET is_free_refresh_available = 1 WHERE is_free_refresh_available = 0'
        );

        $io->success(sprintf('Free shop refresh restored for %d store(s).', $affected));

        return Command::SUCCESS;
    }
}
