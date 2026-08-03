<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WearableItemTemplate;
use App\Enum\WearableItemType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WearableItemTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WearableItemTemplate::class);
    }

    /**
     * @return list<WearableItemTemplate>
     */
    public function findAvailableForTypeAndLevel(WearableItemType $type, int $playerLevel): array
    {
        $level = max(1, $playerLevel);

        return $this->createQueryBuilder('t')
            ->andWhere('t.type = :type')
            ->andWhere('t.minLevel <= :level')
            ->andWhere('t.maxLevel >= :level')
            ->setParameter('type', $type)
            ->setParameter('level', $level)
            ->orderBy('t.publicCode', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByPublicCode(string $publicCode): ?WearableItemTemplate
    {
        return $this->findOneBy(['publicCode' => $publicCode]);
    }
}
