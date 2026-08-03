<?php

declare(strict_types=1);

namespace App\Service\Progression\TimedActivity;

use App\Entity\Mission;
use App\Entity\Training;
use App\Entity\User;
use App\Entity\Work;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use Doctrine\ORM\EntityManagerInterface;

final class OwnedTimedActivityResolver
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function resolveMission(User $user, int $id): Mission
    {
        return $this->resolve($user, Mission::class, $id, 'missionNotFound', 'missionOwnershipRequired');
    }

    public function resolveWork(User $user, int $id): Work
    {
        return $this->resolve($user, Work::class, $id, 'workNotFound', 'workOwnershipRequired');
    }

    public function resolveTraining(User $user, int $id): Training
    {
        return $this->resolve($user, Training::class, $id, 'trainingNotFound', 'trainingOwnershipRequired');
    }

    /**
     * @template T of Mission|Work|Training
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function resolve(User $user, string $class, int $id, string $notFoundKey, string $ownershipKey): Mission|Work|Training
    {
        $entity = $this->entityManager->getRepository($class)->find($id);
        if (!$entity instanceof $class) {
            throw new ResourceNotFoundException($notFoundKey);
        }

        if ($entity->getUser()?->getId() !== $user->getId()) {
            throw new OperationForbiddenException($ownershipKey);
        }

        return $entity;
    }
}
