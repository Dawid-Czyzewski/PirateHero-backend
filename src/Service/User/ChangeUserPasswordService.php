<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User;
use App\Exception\BusinessRuleException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ChangeUserPasswordService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * @throws BusinessRuleException
     */
    public function changePassword(
        User $user,
        string $currentPassword,
        string $newPassword,
        string $newPasswordRepeat,
    ): void {
        if ($currentPassword === '') {
            throw new BusinessRuleException('changePasswordCurrentRequired');
        }
        if ($newPassword === '') {
            throw new BusinessRuleException('changePasswordNewRequired');
        }
        if (strlen($newPassword) < 6) {
            throw new BusinessRuleException('changePasswordNewTooShort');
        }
        if ($newPassword !== $newPasswordRepeat) {
            throw new BusinessRuleException('changePasswordNewMismatch');
        }

        $managed = $this->entityManager->find(User::class, $user->getId());
        if (!$managed instanceof User) {
            throw new BusinessRuleException('changePasswordSessionInvalid');
        }

        if (!$this->passwordHasher->isPasswordValid($managed, $currentPassword)) {
            throw new BusinessRuleException('changePasswordCurrentWrong');
        }

        if ($this->passwordHasher->isPasswordValid($managed, $newPassword)) {
            throw new BusinessRuleException('changePasswordSameAsCurrent');
        }

        $managed->setPassword($this->passwordHasher->hashPassword($managed, $newPassword));
        $this->entityManager->flush();
    }
}
