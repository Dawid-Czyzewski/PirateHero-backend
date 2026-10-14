<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CompanionTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class CompanionTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanionTemplate::class);
    }

    public function findOneByCode(string $code): ?CompanionTemplate
    {
        return $this->findOneBy(['code' => $code]);
    }

    /**
     * @return list<CompanionTemplate>
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('ct')
            ->orderBy('ct.sortOrder', 'ASC')
            ->addOrderBy('ct.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
