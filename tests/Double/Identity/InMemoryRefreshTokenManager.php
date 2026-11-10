<?php

declare(strict_types=1);

namespace App\Tests\Double\Identity;

use App\Identity\Infrastructure\Security\RefreshToken;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;

final class InMemoryRefreshTokenManager implements RefreshTokenManagerInterface
{
    /** @var array<string, RefreshTokenInterface> */
    private array $tokens = [];

    public function get(string $refreshToken): ?RefreshTokenInterface
    {
        return $this->tokens[$refreshToken] ?? null;
    }

    public function getLastFromUsername(string $username): ?RefreshTokenInterface
    {
        $owned = array_filter($this->tokens, static fn (RefreshTokenInterface $token): bool => $token->getUsername() === $username);

        return array_last($owned);
    }

    public function save(RefreshTokenInterface $refreshToken): void
    {
        $this->tokens[(string) $refreshToken->getRefreshToken()] = $refreshToken;
    }

    public function delete(RefreshTokenInterface $refreshToken, bool $andFlush = true): int
    {
        $key = (string) $refreshToken->getRefreshToken();
        $existed = isset($this->tokens[$key]);
        unset($this->tokens[$key]);

        return (int) $existed;
    }

    public function revokeAllInvalid(?\DateTimeInterface $datetime = null, bool $andFlush = true): array
    {
        return [];
    }

    public function revokeAllInvalidBatch(?\DateTimeInterface $datetime = null, ?int $batchSize = null, int $offset = 0, bool $andFlush = true): array
    {
        return [];
    }

    public function getClass(): string
    {
        return RefreshToken::class;
    }
}
