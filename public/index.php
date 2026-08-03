<?php

declare(strict_types=1);

use App\Kernel;

// Shared hosting: app front controller lives under /api (public_html/api/...).
// Symfony would strip that subdirectory from pathInfo, but Api Platform routes
// are registered with the /api prefix — keep REQUEST_URI as pathInfo.
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
if (
    $scriptName === '/api/index.php'
    || str_ends_with($scriptName, '/api/index.php')
    || str_ends_with($scriptName, '/api/public/index.php')
) {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
