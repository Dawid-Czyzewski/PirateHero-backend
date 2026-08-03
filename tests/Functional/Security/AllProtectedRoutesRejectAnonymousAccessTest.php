<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Tests\Functional\ApiWebTestCase;

final class AllProtectedRoutesRejectAnonymousAccessTest extends ApiWebTestCase
{
    public function testProtectedApiRoutesRejectAnonymousUser(): void
    {
        $client = static::ensureTestClient();
        $router = static::getContainer()->get('router');
        $tested = 0;

        foreach ($router->getRouteCollection()->all() as $route) {
            $path = $route->getPath();
            if (!str_starts_with($path, '/api')) {
                continue;
            }
            $interpolated = $this->interpolatePath($path);
            if ($this->isPublicApiPath($interpolated)) {
                continue;
            }
            if (str_starts_with($interpolated, '/api/docs') || str_starts_with($interpolated, '/api/contexts')) {
                continue;
            }
            if (str_starts_with($interpolated, '/api/.well-known') || str_starts_with($interpolated, '/api/validation_errors')) {
                continue;
            }
            $methods = $route->getMethods();
            if ($methods === []) {
                $methods = ['GET'];
            }
            foreach ($methods as $method) {
                if ($method === 'OPTIONS') {
                    continue;
                }
                ++$tested;
                $client->request($method, $interpolated, [], [], ['CONTENT_TYPE' => 'application/json'], '{}');
                $status = $client->getResponse()->getStatusCode();
                self::assertContains($status, [401, 403, 404], sprintf('%s %s — expected 401/403/404, got %d', $method, $interpolated, $status));
            }
        }

        self::assertGreaterThan(30, $tested, 'Expected a large number of declared API operations under /api');
    }
}
