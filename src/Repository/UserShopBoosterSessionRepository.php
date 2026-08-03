<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserShopBoosterSession;
use App\Enum\ShopBoosterCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserShopBoosterSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserShopBoosterSession::class);
    }

    /**
     * @return list<UserShopBoosterSession>
     */
    public function findNonExpiredByUser(User $user, \DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.shopBooster', 'b')->addSelect('b')
            ->andWhere('u.user = :user')->setParameter('user', $user)
            ->andWhere('u.expiresAt > :now')->setParameter('now', $now)
            ->orderBy('u.expiresAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<UserShopBoosterSession>
     */
    public function findNonExpiredForUserInCategory(
        User $user,
        ShopBoosterCategory $category,
        \DateTimeImmutable $now,
    ): array {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.shopBooster', 'b')
            ->where('u.user = :user')->setParameter('user', $user)
            ->andWhere('u.expiresAt > :now')->setParameter('now', $now)
            ->andWhere('b.category = :cat')->setParameter('cat', $category)
            ->getQuery()
            ->getResult();
    }

    public function deleteExpiredForUser(User $user, \DateTimeImmutable $now): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->delete(UserShopBoosterSession::class, 's')
            ->where('s.user = :user')
            ->andWhere('s.expiresAt <= :now')
            ->setParameter('user', $user)
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();
    }
}
