<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Contracts\HttpClient\ResponseInterface;

final class CorsTest extends FunctionalTestCase
{
    private const FRONTEND_ORIGIN = 'http://localhost:3000';

    public function testPreflightFromFrontendAllowsCredentials(): void
    {
        $headers = $this->preflight(self::FRONTEND_ORIGIN)->getHeaders(false);

        self::assertSame([self::FRONTEND_ORIGIN], $headers['access-control-allow-origin'] ?? null);
        self::assertSame(['true'], $headers['access-control-allow-credentials'] ?? null);
    }

    public function testPreflightFromForeignOriginIsNotAllowed(): void
    {
        $headers = $this->preflight('http://evil.example')->getHeaders(false);

        self::assertArrayNotHasKey('access-control-allow-origin', $headers);
    }

    private function preflight(string $origin): ResponseInterface
    {
        return $this->client->request('OPTIONS', '/api/auth/login', ['headers' => [
            'Origin' => $origin,
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'content-type',
        ]]);
    }
}
