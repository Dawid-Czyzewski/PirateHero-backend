<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByEmailIgnoreCase(string $email): ?User
    {
        $normalized = strtolower(trim($email));
        if ($normalized === '') {
            return null;
        }

        return $this->createQueryBuilder('u')
            ->where('LOWER(u.email) = :email')
            ->setParameter('email', $normalized)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Activated users excluding {@see $user}, with stats/equipment joins for skill averages.
     *
     * @return list<User>
     */
    public function findActivatedUsersExcluding(User $user): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.userBaseStatistics', 's')
            ->leftJoin('u.userEquipment', 'eq')
            ->leftJoin('eq.userEquipmentSlots', 'slots')
            ->leftJoin('slots.wearableItem', 'item')
            ->leftJoin('item.statistics', 'itemStats')
            ->addSelect('s', 'eq', 'slots', 'item', 'itemStats')
            ->andWhere('u != :user')
            ->andWhere('u.activateToken IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * @deprecated Use {@see SimilarUsersResolver::findSimilarByAverageSkill()}
     *
     * @return list<User>
     */
    public function findSimilarUsersByAverageSkill(User $user, int $limit = 10): array
    {
        $targetAvg = $user->getAverageSkill();
        $users = $this->findActivatedUsersExcluding($user);

        usort($users, static function (User $a, User $b) use ($targetAvg) {
            $diffA = abs($a->getAverageSkill() - $targetAvg);
            $diffB = abs($b->getAverageSkill() - $targetAvg);

            return $diffA <=> $diffB;
        });

        return array_slice($users, 0, $limit);
    }
}
