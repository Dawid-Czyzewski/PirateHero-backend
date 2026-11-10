<?php

declare(strict_types=1);

namespace App\Tests\Functional;

final class HealthAndDocsTest extends FunctionalTestCase
{
    public function testHealthIsPublicAndChecksDatabase(): void
    {
        $data = self::data($this->getJson('/api/health'));

        self::assertResponseIsSuccessful();
        self::assertSame(['status' => 'ok', 'db' => 'ok'], $data);
    }

    public function testDocsArePublic(): void
    {
        $this->client->request('GET', '/api/docs', ['headers' => ['Accept' => 'text/html']]);

        self::assertResponseIsSuccessful();
    }

    public function testCorsAllowsOnlyFrontendOrigin(): void
    {
        $this->client->request('OPTIONS', '/api/auth/login', ['headers' => [
            'Origin' => 'http://localhost:3000',
            'Access-Control-Request-Method' => 'POST',
        ]]);
        self::assertResponseHeaderSame('Access-Control-Allow-Origin', 'http://localhost:3000');

        $this->client->request('OPTIONS', '/api/auth/login', ['headers' => [
            'Origin' => 'http://evil.example',
            'Access-Control-Request-Method' => 'POST',
        ]]);
        self::assertResponseNotHasHeader('Access-Control-Allow-Origin');
    }
}
