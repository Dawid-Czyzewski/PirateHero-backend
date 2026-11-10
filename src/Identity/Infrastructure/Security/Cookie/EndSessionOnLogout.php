<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security\Cookie;

use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LogoutEvent::class, dispatcher: 'security.event_dispatcher.auth')]
final readonly class EndSessionOnLogout
{
    public function __construct(
        private AuthCookies $cookies,
        private RefreshTokenManagerInterface $refreshTokens,
    ) {
    }

    public function __invoke(LogoutEvent $event): void
    {
        $this->revokeRefreshToken($event->getRequest());

        $response = new Response(status: Response::HTTP_NO_CONTENT);
        $this->cookies->expireOn($response);
        $event->setResponse($response);
    }

    private function revokeRefreshToken(Request $request): void
    {
        $value = $this->cookies->refreshTokenOf($request);
        $refreshToken = null === $value ? null : $this->refreshTokens->get($value);

        if (null !== $refreshToken) {
            $this->refreshTokens->delete($refreshToken);
        }
    }
}
