<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

if (is_file(__DIR__.'/.env')) {
    (new Dotenv())->bootEnv(__DIR__.'/.env');
}

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', filter_var($_SERVER['APP_DEBUG'] ?? true, \FILTER_VALIDATE_BOOLEAN));
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
