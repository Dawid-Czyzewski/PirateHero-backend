<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\RefreshToken;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

readonly class TokenRefreshService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private JWTTokenManagerInterface $jwtManager,
        private UserProviderInterface $userProvider,
    ) {
    }

    /**
     * @return array{token: string}
     */
    public function refresh(string $refreshTokenValue): array
    {
        $refreshToken = $this->entityManager->getRepository(RefreshToken::class)
            ->findOneBy(['refreshToken' => $refreshTokenValue]);

        if (!$refreshToken instanceof RefreshToken) {
            throw new UnauthorizedHttpException('Bearer', 'refreshTokenNotFound');
        }

        if (!$refreshToken->isValid()) {
            throw new UnauthorizedHttpException('Bearer', 'refreshTokenInvalid');
        }

        $user = $this->userProvider->loadUserByIdentifier($refreshToken->getUsername());

        return ['token' => $this->jwtManager->create($user)];
    }
}
