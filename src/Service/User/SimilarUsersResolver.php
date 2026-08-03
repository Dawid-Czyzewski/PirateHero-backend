<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User;
use App\Repository\UserRepository;

final readonly class SimilarUsersResolver
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    /**
     * @return list<User>
     */
    public function findSimilarByAverageSkill(User $user, int $limit = 10): array
    {
        $targetAvg = $user->getAverageSkill();
        $users = $this->userRepository->findActivatedUsersExcluding($user);

        usort($users, static function (User $a, User $b) use ($targetAvg): int {
            $diffA = abs($a->getAverageSkill() - $targetAvg);
            $diffB = abs($b->getAverageSkill() - $targetAvg);

            return $diffA <=> $diffB;
        });

        return array_slice($users, 0, $limit);
    }
}
