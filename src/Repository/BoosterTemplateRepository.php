<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\BoosterTemplate;
use App\Enum\BoosterType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BoosterTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BoosterTemplate::class);
    }

    public function findByTypeAndTier(BoosterType $type, int $tier): ?BoosterTemplate
    {
        return $this->createQueryBuilder('bt')
            ->where('bt.type = :type')
            ->andWhere('bt.tier = :tier')
            ->setParameter('type', $type)
            ->setParameter('tier', $tier)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
