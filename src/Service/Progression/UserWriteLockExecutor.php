<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Entity\User;
use App\Exception\ResourceNotFoundException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class UserWriteLockExecutor
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param callable(User): T $callback
     *
     * @return T
     */
    public function execute(User $user, callable $callback): mixed
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $lockedUser = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if ($lockedUser === null) {
                throw new ResourceNotFoundException('userNotFound');
            }

            $result = $callback($lockedUser);
            $connection->commit();

            return $result;
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
