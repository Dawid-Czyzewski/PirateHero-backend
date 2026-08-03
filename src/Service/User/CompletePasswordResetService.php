<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CompletePasswordResetService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function completeReset(string $token, string $newPassword, string $newPasswordRepeat): void
    {
        $token = trim($token);
        if ($token === '') {
            throw new BusinessRuleException('passwordResetTokenInvalid');
        }
        if ($newPassword === '') {
            throw new BusinessRuleException('passwordResetNewRequired');
        }
        if (strlen($newPassword) < 6) {
            throw new BusinessRuleException('passwordResetNewTooShort');
        }
        if ($newPassword !== $newPasswordRepeat) {
            throw new BusinessRuleException('passwordResetNewMismatch');
        }

        $user = $this->userRepository->findOneBy(['passwordResetToken' => $token]);
        if ($user === null) {
            throw new ResourceNotFoundException('passwordResetTokenInvalid');
        }

        $expiresAt = $user->getPasswordResetTokenExpiresAt();
        if ($expiresAt === null || $expiresAt < new \DateTimeImmutable()) {
            throw new BusinessRuleException('passwordResetTokenExpired');
        }

        if ($user->getActivateToken() !== null) {
            throw new BusinessRuleException('passwordResetTokenInvalid');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        $user->clearPasswordResetToken();
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
