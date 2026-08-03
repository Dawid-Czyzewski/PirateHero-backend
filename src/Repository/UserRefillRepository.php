<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\UserRefill;
use App\Enum\RefillType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRefillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRefill::class);
    }

    public function findByUserAndType($user, RefillType $type): ?UserRefill
    {
        return $this->findOneBy([
            'user' => $user,
            'type' => $type,
        ]);
    }
}
