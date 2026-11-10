<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security\Cookie;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class AuthCookies
{
    public function __construct(
        #[Autowire('%auth.cookie.access_name%')]
        private string $accessName,
        #[Autowire('%auth.cookie.access_path%')]
        private string $accessPath,
        #[Autowire('%auth.cookie.refresh_name%')]
        private string $refreshName,
        #[Autowire('%auth.cookie.refresh_path%')]
        private string $refreshPath,
        #[Autowire('%auth.cookie.secure%')]
        private bool $secure,
        #[Autowire('%auth.cookie.same_site%')]
        private string $sameSite,
    ) {
    }

    public function refreshTokenOf(Request $request): ?string
    {
        $value = $request->cookies->get($this->refreshName);

        return \is_string($value) && '' !== $value ? $value : null;
    }

    public function expireOn(Response $response): void
    {
        $this->expire($response, $this->accessName, $this->accessPath);
        $this->expire($response, $this->refreshName, $this->refreshPath);
    }

    private function expire(Response $response, string $name, string $path): void
    {
        $response->headers->clearCookie($name, $path, null, $this->secure, true, $this->sameSite);
    }
}
