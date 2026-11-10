<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    public function __construct(private readonly Connection $connection)
    {
    }

    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        try {
            $this->connection->executeQuery('SELECT 1')->fetchOne();
            $db = 'ok';
        } catch (\Throwable) {
            $db = 'error';
        }

        $healthy = 'ok' === $db;

        return new JsonResponse(
            ['status' => $healthy ? 'ok' : 'error', 'db' => $db],
            $healthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }
}
