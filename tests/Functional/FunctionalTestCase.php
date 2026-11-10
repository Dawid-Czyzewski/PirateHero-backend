<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

abstract class FunctionalTestCase extends ApiTestCase
{
    use Factories;
    use ResetDatabase;

    protected static ?bool $alwaysBootKernel = true;

    protected Client $client;

    protected function setUp(): void
    {
        $this->client = static::createClient(defaultOptions: [
            'headers' => ['Accept' => 'application/json'],
        ]);

        $limiterCache = static::getContainer()->get('cache.rate_limiter');
        self::assertInstanceOf(CacheItemPoolInterface::class, $limiterCache);
        $limiterCache->clear();
    }

    /**
     * @param array<string, mixed> $body
     */
    protected function postJson(string $uri, array $body, ?string $token = null): ResponseInterface
    {
        $options = ['json' => $body];
        if (null !== $token) {
            $options['auth_bearer'] = $token;
        }

        return $this->client->request('POST', $uri, $options);
    }

    protected function getJson(string $uri, ?string $token = null): ResponseInterface
    {
        return $this->client->request('GET', $uri, null === $token ? [] : ['auth_bearer' => $token]);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function data(ResponseInterface $response): array
    {
        return $response->toArray(false);
    }

    /**
     * @return array{token: string, refresh_token: string}
     */
    protected function login(string $email, string $password): array
    {
        $data = self::data($this->postJson('/api/auth/login', ['email' => $email, 'password' => $password]));
        self::assertResponseIsSuccessful();
        self::assertIsString($data['token'] ?? null);
        self::assertIsString($data['refresh_token'] ?? null);

        return ['token' => $data['token'], 'refresh_token' => $data['refresh_token']];
    }
}
