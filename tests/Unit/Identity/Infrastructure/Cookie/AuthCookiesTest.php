<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Cookie;

use App\Identity\Infrastructure\Security\Cookie\AuthCookies;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthCookiesTest extends TestCase
{
    public function testReadsRefreshTokenFromItsCookie(): void
    {
        $request = new Request(cookies: ['refresh_token' => 'abc', 'access_token' => 'jwt']);

        self::assertSame('abc', self::cookies()->refreshTokenOf($request));
    }

    public function testReturnsNullWithoutRefreshCookie(): void
    {
        self::assertNull(self::cookies()->refreshTokenOf(new Request(cookies: ['access_token' => 'jwt'])));
    }

    public function testReturnsNullForEmptyRefreshCookie(): void
    {
        self::assertNull(self::cookies()->refreshTokenOf(new Request(cookies: ['refresh_token' => ''])));
    }

    public function testExpiresBothCookiesWithTheirAttributes(): void
    {
        $response = new Response();

        self::cookies(secure: true, sameSite: Cookie::SAMESITE_STRICT)->expireOn($response);

        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie;
        }

        self::assertSame(['access_token', 'refresh_token'], array_keys($cookies));
        self::assertSame('/api', $cookies['access_token']->getPath());
        self::assertSame('/api/auth', $cookies['refresh_token']->getPath());
        foreach ($cookies as $cookie) {
            self::assertTrue($cookie->isCleared());
            self::assertTrue($cookie->isHttpOnly());
            self::assertTrue($cookie->isSecure());
            self::assertSame(Cookie::SAMESITE_STRICT, $cookie->getSameSite());
        }
    }

    private static function cookies(bool $secure = false, string $sameSite = Cookie::SAMESITE_LAX): AuthCookies
    {
        return new AuthCookies('access_token', '/api', 'refresh_token', '/api/auth', $secure, $sameSite);
    }
}
