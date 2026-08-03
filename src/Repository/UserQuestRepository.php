<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserQuest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserQuestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserQuest::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('uq')
            ->where('uq.user = :user')
            ->setParameter('user', $user)
            ->leftJoin('uq.questTemplate', 'qt')
            ->addSelect('qt')
            ->orderBy('qt.order', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndTemplate(User $user, int $templateId): ?UserQuest
    {
        return $this->createQueryBuilder('uq')
            ->innerJoin('uq.questTemplate', 'qt')
            ->addSelect('qt')
            ->where('uq.user = :user')
            ->andWhere('qt.id = :templateId')
            ->setParameter('user', $user)
            ->setParameter('templateId', $templateId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
