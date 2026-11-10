<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Factory\UserFactory;

final class AuthTest extends FunctionalTestCase
{
    public function testLoginReturnsAccessAndRefreshToken(): void
    {
        UserFactory::createOne(['email' => 'gracz@example.com']);

        $tokens = $this->login('gracz@example.com', UserFactory::DEFAULT_PASSWORD);

        self::assertCount(3, explode('.', $tokens['token']), 'Access token is a JWT');
        self::assertNotEmpty($tokens['refresh_token']);
    }

    public function testLoginWithWrongPasswordFails(): void
    {
        UserFactory::createOne(['email' => 'gracz@example.com']);

        $data = self::data($this->postJson('/api/auth/login', ['email' => 'gracz@example.com', 'password' => 'zlehaslo123']));

        self::assertResponseStatusCodeSame(401);
        self::assertSame('Invalid credentials.', $data['message'] ?? null);
    }

    public function testLoginWithUnknownEmailFails(): void
    {
        $data = self::data($this->postJson('/api/auth/login', ['email' => 'nikt@example.com', 'password' => 'tajnehaslo']));

        self::assertResponseStatusCodeSame(401);
        self::assertSame('Invalid credentials.', $data['message'] ?? null);
    }

    public function testRefreshReturnsNewTokensAndInvalidatesOldRefreshToken(): void
    {
        UserFactory::createOne(['email' => 'gracz@example.com']);
        $tokens = $this->login('gracz@example.com', UserFactory::DEFAULT_PASSWORD);

        $data = self::data($this->postJson('/api/auth/refresh', ['refresh_token' => $tokens['refresh_token']]));

        self::assertResponseIsSuccessful();
        self::assertIsString($data['token'] ?? null);
        self::assertIsString($data['refresh_token'] ?? null);
        self::assertNotSame($tokens['refresh_token'], $data['refresh_token']);

        $this->postJson('/api/auth/refresh', ['refresh_token' => $tokens['refresh_token']]);
        self::assertResponseStatusCodeSame(401);
    }

    public function testRefreshedAccessTokenWorks(): void
    {
        UserFactory::createOne(['email' => 'gracz@example.com']);
        $tokens = $this->login('gracz@example.com', UserFactory::DEFAULT_PASSWORD);

        $data = self::data($this->postJson('/api/auth/refresh', ['refresh_token' => $tokens['refresh_token']]));
        self::assertIsString($data['token'] ?? null);

        $this->getJson('/api/me', $data['token']);
        self::assertResponseIsSuccessful();
    }

    public function testRefreshWithInvalidTokenFails(): void
    {
        $data = self::data($this->postJson('/api/auth/refresh', ['refresh_token' => 'nie-ma-takiego-tokena']));

        self::assertResponseStatusCodeSame(401);
        self::assertSame('JWT Refresh Token Not Found', $data['message'] ?? null);
    }

    public function testRefreshWithoutTokenFails(): void
    {
        $data = self::data($this->postJson('/api/auth/refresh', []));

        self::assertResponseStatusCodeSame(401);
        self::assertSame('Missing JWT Refresh Token', $data['message'] ?? null);
    }
}
