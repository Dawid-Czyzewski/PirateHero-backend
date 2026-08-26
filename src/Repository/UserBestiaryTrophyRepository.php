<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserBestiaryTrophy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBestiaryTrophy>
 */
class UserBestiaryTrophyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBestiaryTrophy::class);
    }

    public function findOneForUserAndCode(User $user, string $code): ?UserBestiaryTrophy
    {
        return $this->findOneBy(['user' => $user, 'trophyCode' => $code]);
    }

    /**
     * @return array<string, UserBestiaryTrophy>
     */
    public function getMapForUser(User $user): array
    {
        $map = [];
        foreach ($this->findBy(['user' => $user]) as $row) {
            $map[$row->getTrophyCode()] = $row;
        }

        return $map;
    }
}
