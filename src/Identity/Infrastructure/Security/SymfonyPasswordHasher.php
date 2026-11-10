<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\Model\HashedPassword;
use App\Identity\Domain\Model\PlainPassword;
use App\Identity\Domain\Service\PasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final readonly class SymfonyPasswordHasher implements PasswordHasher
{
    public function __construct(private PasswordHasherFactoryInterface $hasherFactory)
    {
    }

    public function hash(PlainPassword $password): HashedPassword
    {
        return new HashedPassword(
            $this->hasherFactory->getPasswordHasher(SecurityUser::class)->hash($password->reveal()),
        );
    }
}
