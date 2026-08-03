<?php

declare(strict_types=1);

namespace App\Tests\Functional\Contract;

use App\Tests\Functional\ApiWebTestCase;

final class AllApiRoutesRuntimeMatrixTest extends ApiWebTestCase
{
    public function testEveryApiRouteAuthenticatedDoesNotCrashWith500(): void
    {
        $client = $this->createAuthenticatedClient();
        $router = static::getContainer()->get('router');
        $tested = 0;

        foreach ($router->getRouteCollection()->all() as $route) {
            $path = $route->getPath();
            if (!str_starts_with($path, '/api')) {
                continue;
            }

            $targetPath = $this->interpolatePath($path);
            $methods = $route->getMethods();
            if ($methods === []) {
                $methods = ['GET'];
            }

            foreach ($methods as $method) {
                if ($method === 'OPTIONS') {
                    continue;
                }
                ++$tested;

                if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
                    $client->request($method, $targetPath, [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
                } else {
                    $client->request($method, $targetPath);
                }

                $status = $client->getResponse()->getStatusCode();
                self::assertNotSame(500, $status, sprintf('%s %s returned 500', $method, $targetPath));
            }
        }

        self::assertGreaterThan(35, $tested, 'Expected large route coverage for /api operations');
    }

    public function testEveryApiWriteRouteRejectsMalformedJsonWithout500(): void
    {
        $client = $this->createAuthenticatedClient();
        $router = static::getContainer()->get('router');
        $tested = 0;

        foreach ($router->getRouteCollection()->all() as $route) {
            $path = $route->getPath();
            if (!str_starts_with($path, '/api')) {
                continue;
            }

            $methods = $route->getMethods();
            if ($methods === []) {
                continue;
            }

            foreach ($methods as $method) {
                if (!in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
                    continue;
                }
                ++$tested;
                $targetPath = $this->interpolatePath($path);

                $client->request($method, $targetPath, [], [], ['CONTENT_TYPE' => 'application/json'], '{');
                $status = $client->getResponse()->getStatusCode();
                self::assertNotSame(500, $status, sprintf('%s %s malformed JSON returned 500', $method, $targetPath));
                self::assertContains($status, [200, 201, 400, 401, 403, 404, 405, 415, 422], sprintf('%s %s unexpected code %d', $method, $targetPath, $status));
            }
        }

        self::assertGreaterThan(20, $tested, 'Expected many write operations in API');
    }
}
