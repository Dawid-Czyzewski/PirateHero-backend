<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\QuestTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class QuestTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestTemplate::class);
    }

    public function findActiveOrdered(): array
    {
        return $this->createQueryBuilder('qt')
            ->where('qt.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('qt.order', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
