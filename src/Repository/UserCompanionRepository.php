<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CompanionTemplate;
use App\Entity\User;
use App\Entity\UserCompanion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class UserCompanionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserCompanion::class);
    }

    public function findOneForUserAndTemplate(User $user, CompanionTemplate $template): ?UserCompanion
    {
        return $this->findOneBy([
            'user' => $user,
            'companionTemplate' => $template,
        ]);
    }

    /**
     * @return array<string, UserCompanion> keyed by companion template code
     */
    public function getOwnedMapForUser(User $user): array
    {
        $entries = $this->createQueryBuilder('uc')
            ->innerJoin('uc.companionTemplate', 'ct')
            ->addSelect('ct')
            ->where('uc.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($entries as $entry) {
            $template = $entry->getCompanionTemplate();
            if ($template !== null) {
                $map[$template->getCode()] = $entry;
            }
        }

        return $map;
    }
}
