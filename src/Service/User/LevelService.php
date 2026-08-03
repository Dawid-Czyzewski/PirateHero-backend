<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Domain\Constants\ProgressionConstants;
use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Repository\LevelRepository;
use App\Service\Progression\QuestProgressService;
use App\Service\Progression\UserLevelResolver;
use Doctrine\ORM\EntityManagerInterface;

readonly class LevelService
{
    public function __construct(
        private readonly LevelRepository $levelRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SkillPointsService $skillPointsService,
        private readonly QuestProgressService $questProgressService,
    ) {
    }

    /**
     * @return array{
     *   levelUp: bool,
     *   currentLevel: string,
     *   currentExp: int,
     *   expToNextLevel: int
     * }
     */
    public function checkAndUpdateLevel(User $user): array
    {
        $currentLevel = $user->getLevel();
        $currentExp = $user->getExperiencePoints() ?? 0;

        if (!$currentLevel) {
            throw new BusinessRuleException('userLevelNotFound');
        }

        $leveledUp = false;

        while ($currentExp >= $currentLevel->getExpToNextLevel()) {
            $nextLevelName = (string) ((int) $currentLevel->getName() + 1);
            $nextLevel = $this->levelRepository->findOneBy(['name' => $nextLevelName]);

            if (!$nextLevel) {
                throw new ResourceNotFoundException('nextLevelNotFound');
            }

            $currentExp -= $currentLevel->getExpToNextLevel();
            $currentLevel = $nextLevel;
            $leveledUp = true;

            $this->skillPointsService->addFreeSkillPoints($user, ProgressionConstants::ATTRIBUTE_POINTS_PER_LEVEL_UP);
        }

        if (!$leveledUp) {
            return [
                'levelUp' => false,
                'currentLevel' => $currentLevel->getName(),
                'currentExp' => $currentExp,
                'expToNextLevel' => $currentLevel->getExpToNextLevel() - $currentExp,
            ];
        }

        $user->setLevel($currentLevel);
        $user->setExperiencePoints($currentExp);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->questProgressService->updateUserLevel($user, UserLevelResolver::of($user));

        return [
            'levelUp' => true,
            'currentLevel' => $currentLevel->getName(),
            'currentExp' => $currentExp,
            'expToNextLevel' => $currentLevel->getExpToNextLevel(),
        ];
    }
}
