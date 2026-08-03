<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PlayerTitle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PlayerTitleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlayerTitle::class);
    }

    public function findOneByCode(string $code): ?PlayerTitle
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * @return list<PlayerTitle>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('pt')
            ->orderBy('pt.sortOrder', 'ASC')
            ->addOrderBy('pt.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
