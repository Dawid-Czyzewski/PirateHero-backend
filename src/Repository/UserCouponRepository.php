<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Coupon;
use App\Entity\User;
use App\Entity\UserCoupon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserCouponRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCoupon::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('uc')
            ->leftJoin('uc.coupon', 'c')
            ->addSelect('c')
            ->where('uc.user = :user')
            ->setParameter('user', $user)
            ->orderBy('uc.usedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndCoupon(User $user, Coupon $coupon): ?UserCoupon
    {
        return $this->createQueryBuilder('uc')
            ->where('uc.user = :user')
            ->andWhere('uc.coupon = :coupon')
            ->setParameter('user', $user)
            ->setParameter('coupon', $coupon)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
