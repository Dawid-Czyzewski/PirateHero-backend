<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\TavernJob;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TavernJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TavernJob::class);
    }

    /**
     * @return list<TavernJob>
     */
    public function findForUserOnDate(User $user, \DateTimeImmutable $date): array
    {
        /** @var list<TavernJob> $jobs */
        $jobs = $this->findBy(
            [
                'user' => $user,
                'offeredOn' => $date,
            ],
            ['id' => 'ASC'],
        );

        return $jobs;
    }
}
