<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PlayerTitle;
use App\Entity\User;
use App\Entity\UserTitle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserTitleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserTitle::class);
    }

    public function findOneForUserAndTitle(User $user, PlayerTitle $playerTitle): ?UserTitle
    {
        return $this->findOneBy([
            'user' => $user,
            'playerTitle' => $playerTitle,
        ]);
    }

    /**
     * @return array<string, UserTitle> keyed by title code
     */
    public function getUnlockedMapForUser(User $user): array
    {
        $entries = $this->createQueryBuilder('ut')
            ->innerJoin('ut.playerTitle', 'pt')
            ->addSelect('pt')
            ->where('ut.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($entries as $entry) {
            $title = $entry->getPlayerTitle();
            if ($title !== null) {
                $map[$title->getCode()] = $entry;
            }
        }

        return $map;
    }
}
