<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PremiumShopTransaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PremiumShopTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PremiumShopTransaction::class);
    }

    /**
     * @return list<PremiumShopTransaction>
     */
    public function findRecentForUser(User $user, int $limit = 50): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.purchasedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
