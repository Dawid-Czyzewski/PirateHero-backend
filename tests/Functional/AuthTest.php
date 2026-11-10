<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Factory\UserFactory;
use Symfony\Component\BrowserKit\Cookie as BrowserCookie;
use Symfony\Component\HttpFoundation\Cookie;

final class AuthTest extends FunctionalTestCase
{
    private const EMAIL = 'gracz@example.com';

    public function testLoginSetsHttpOnlyTokenCookies(): void
    {
        UserFactory::createOne(['email' => self::EMAIL]);

        $tokens = $this->login(self::EMAIL, UserFactory::DEFAULT_PASSWORD);

        self::assertCount(3, explode('.', $tokens['token']), 'Access token is a JWT');
        $access = $this->responseCookie(self::ACCESS_COOKIE);
        $refresh = $this->responseCookie(self::REFRESH_COOKIE);
        self::assertNotNull($access);
        self::assertNotNull($refresh);
        self::assertSame('/api', $access->getPath());
        self::assertSame('/api/auth', $refresh->getPath());
        foreach ([$access, $refresh] as $cookie) {
            self::assertTrue($cookie->isHttpOnly());
            self::assertFalse($cookie->isSecure());
            self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        }
    }

    public function testLoginDoesNotExposeTokensInBody(): void
    {
        UserFactory::createOne(['email' => self::EMAIL]);

        $response = $this->postJson('/api/auth/login', ['email' => self::EMAIL, 'password' => UserFactory::DEFAULT_PASSWORD]);

        self::assertResponseIsSuccessful();
        self::assertSame('', $response->getContent(false));
    }

    public function testLoginWithWrongPasswordFails(): void
    {
        UserFactory::createOne(['email' => self::EMAIL]);

        $data = self::data($this->postJson('/api/auth/login', ['email' => self::EMAIL, 'password' => 'zlehaslo123']));

        self::assertResponseStatusCodeSame(401);
        self::assertSame('Invalid credentials.', $data['message'] ?? null);
        self::assertNull($this->responseCookie(self::ACCESS_COOKIE));
    }

    public function testLoginWithUnknownEmailFails(): void
    {
        $data = self::data($this->postJson('/api/auth/login', ['email' => 'nikt@example.com', 'password' => 'tajnehaslo']));

        self::assertResponseStatusCodeSame(401);
        self::assertSame('Invalid credentials.', $data['message'] ?? null);
    }

    public function testAccessCookieAuthenticatesRequests(): void
    {
        UserFactory::createOne(['email' => self::EMAIL]);
        $this->login(self::EMAIL, UserFactory::DEFAULT_PASSWORD);

        $data = self::data($this->getJson('/api/me'));

        self::assertResponseIsSuccessful();
        self::assertSame(self::EMAIL, $data['email'] ?? null);
    }

    public function testRefreshWithCookieRotatesTokens(): void
    {
        UserFactory::createOne(['email' => self::EMAIL]);
        $tokens = $this->login(self::EMAIL, UserFactory::DEFAULT_PASSWORD);

        $response = $this->postJson('/api/auth/refresh', []);

        self::assertResponseIsSuccessful();
        self::assertSame('', $response->getContent(false));
        self::assertNotNull($this->responseCookie(self::ACCESS_COOKIE));
        self::assertNotSame($tokens['refresh_token'], $this->responseCookie(self::REFRESH_COOKIE)?->getValue());

        $this->client->getCookieJar()->clear();
        $this->postJson('/api/auth/refresh', [self::REFRESH_COOKIE => $tokens['refresh_token']]);
        self::assertResponseStatusCodeSame(401);
    }

    public function testRefreshedAccessCookieWorks(): void
    {
        UserFactory::createOne(['email' => self::EMAIL]);
        $this->login(self::EMAIL, UserFactory::DEFAULT_PASSWORD);
        $this->postJson('/api/auth/refresh', []);

        $this->getJson('/api/me');

        self::assertResponseIsSuccessful();
    }

    public function testRefreshIgnoresInvalidAccessCookie(): void
    {
        UserFactory::createOne(['email' => self::EMAIL]);
        $this->login(self::EMAIL, UserFactory::DEFAULT_PASSWORD);
        $this->client->getCookieJar()->set(new BrowserCookie(self::ACCESS_COOKIE, 'not.a.jwt', null, '/api', 'localhost'));

        $this->postJson('/api/auth/refresh', []);

        self::assertResponseIsSuccessful();
    }

    public function testRefreshWithInvalidTokenFails(): void
    {
        $data = self::data($this->postJson('/api/auth/refresh', [self::REFRESH_COOKIE => 'nie-ma-takiego-tokena']));

        self::assertResponseStatusCodeSame(401);
        self::assertSame('JWT Refresh Token Not Found', $data['message'] ?? null);
    }

    public function testRefreshWithoutTokenFails(): void
    {
        $data = self::data($this->postJson('/api/auth/refresh', []));

        self::assertResponseStatusCodeSame(401);
        self::assertSame('Missing JWT Refresh Token', $data['message'] ?? null);
    }

    public function testLogoutClearsCookiesAndRevokesRefreshToken(): void
    {
        UserFactory::createOne(['email' => self::EMAIL]);
        $tokens = $this->login(self::EMAIL, UserFactory::DEFAULT_PASSWORD);

        $this->postJson('/api/auth/logout', []);

        self::assertResponseStatusCodeSame(204);
        $this->assertSessionCookiesCleared();

        $this->getJson('/api/me');
        self::assertResponseStatusCodeSame(401);

        $this->postJson('/api/auth/refresh', [self::REFRESH_COOKIE => $tokens['refresh_token']]);
        self::assertResponseStatusCodeSame(401);
    }

    public function testLogoutWithoutSessionStillClearsCookies(): void
    {
        $this->postJson('/api/auth/logout', []);

        self::assertResponseStatusCodeSame(204);
        $this->assertSessionCookiesCleared();
    }

    public function testLogoutRequiresPost(): void
    {
        $this->getJson('/api/auth/logout');

        self::assertResponseStatusCodeSame(405);
    }

    private function assertSessionCookiesCleared(): void
    {
        foreach ([self::ACCESS_COOKIE, self::REFRESH_COOKIE] as $name) {
            self::assertTrue((bool) $this->responseCookie($name)?->isCleared(), $name.' cookie is cleared');
        }
    }
}
