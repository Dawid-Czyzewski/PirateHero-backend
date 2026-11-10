<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Infrastructure\Cookie;

use App\Identity\Infrastructure\Security\Cookie\AuthCookies;
use App\Identity\Infrastructure\Security\Cookie\EndSessionOnLogout;
use App\Identity\Infrastructure\Security\RefreshToken;
use App\Tests\Double\Identity\InMemoryRefreshTokenManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class EndSessionOnLogoutTest extends TestCase
{
    private InMemoryRefreshTokenManager $refreshTokens;

    protected function setUp(): void
    {
        $this->refreshTokens = new InMemoryRefreshTokenManager();
    }

    public function testRevokesRefreshTokenFromCookie(): void
    {
        $this->refreshTokens->save((new RefreshToken())->setRefreshToken('abc')->setUsername('gracz@example.com'));

        $this->logout(new Request(cookies: ['refresh_token' => 'abc']));

        self::assertNull($this->refreshTokens->get('abc'));
    }

    public function testKeepsOtherRefreshTokens(): void
    {
        $this->refreshTokens->save((new RefreshToken())->setRefreshToken('other')->setUsername('inny@example.com'));

        $this->logout(new Request(cookies: ['refresh_token' => 'abc']));

        self::assertNotNull($this->refreshTokens->get('other'));
    }

    public function testRespondsWithNoContentAndExpiredCookies(): void
    {
        $response = $this->logout(new Request());

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $names = array_map(static fn ($cookie): string => $cookie->getName(), $response->headers->getCookies());
        self::assertSame(['access_token', 'refresh_token'], $names);
    }

    private function logout(Request $request): Response
    {
        $cookies = new AuthCookies('access_token', '/api', 'refresh_token', '/api/auth', false, 'lax');
        $event = new LogoutEvent($request, null);

        (new EndSessionOnLogout($cookies, $this->refreshTokens))($event);

        $response = $event->getResponse();
        self::assertNotNull($response);

        return $response;
    }
}
