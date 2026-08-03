<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Exception\ResourceNotFoundException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
    ) {
    }

    public function activateAccount(string $token): void
    {
        $user = $this->userRepository->findOneBy(['activateToken' => $token]);

        if (!$user) {
            throw new ResourceNotFoundException('invalidActivationToken');
        }

        $user->setActivateToken(null);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
