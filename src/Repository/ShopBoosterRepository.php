<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ShopBooster;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ShopBoosterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopBooster::class);
    }

    public function findOneByPublicCode(string $publicCode): ?ShopBooster
    {
        return $this->findOneBy(['publicCode' => $publicCode]);
    }

    /**
     * @return list<ShopBooster>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.publicCode', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
