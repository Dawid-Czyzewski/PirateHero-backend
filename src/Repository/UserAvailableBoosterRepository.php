<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserAvailableBooster;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserAvailableBoosterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAvailableBooster::class);
    }

    public function findByUserAndTemplate(string $userId, int $templateId): ?UserAvailableBooster
    {
        return $this->createQueryBuilder('uab')
            ->where('uab.user = :userId')
            ->andWhere('uab.boosterTemplate = :templateId')
            ->setParameter('userId', $userId)
            ->setParameter('templateId', $templateId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
