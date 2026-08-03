<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Http\ApiEnvelope;
use App\Http\ProblemJson;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private JWTTokenManagerInterface $jwtManager;
    private RefreshTokenManagerInterface $refreshTokenManager;

    public function __construct(JWTTokenManagerInterface $jwtManager, RefreshTokenManagerInterface $refreshTokenManager)
    {
        $this->jwtManager = $jwtManager;
        $this->refreshTokenManager = $refreshTokenManager;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return ProblemJson::response(Response::HTTP_UNAUTHORIZED, 'invalidUser');
        }

        if ($user->getActivateToken() !== null) {
            return ProblemJson::response(Response::HTTP_FORBIDDEN, 'accountNotActivated');
        }

        $jwt = $this->jwtManager->create($user);
        if ($jwt === '') {
            throw new \RuntimeException(
                'JWT signing returned an empty token. Check lexik_jwt_authentication (e.g. JWT_SECRET_STRING and HS256).'
            );
        }

        $existing = $this->refreshTokenManager->getLastFromUsername($user->getUserIdentifier());
        if ($existing && $existing->isValid()) {
            $refreshToken = $existing;
        } else {
            if ($existing) {
                $this->refreshTokenManager->delete($existing);
            }
            $refreshToken = new RefreshToken();
            $refreshToken->setRefreshToken(bin2hex(random_bytes(64)));
            $refreshToken->setUsername($user->getUserIdentifier());
            $refreshToken->setValid((new \DateTime())->modify('+30 days'));
            $this->refreshTokenManager->save($refreshToken);
        }

        return ApiEnvelope::jsonResponse([
            'user' => [
                'id' => $user->getId(),
                'roles' => $user->getRoles(),
            ],
            'token' => $jwt,
            'refresh_token' => $refreshToken->getRefreshToken(),
        ], null);
    }
}
