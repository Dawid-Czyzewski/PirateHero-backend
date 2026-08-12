<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User;
use App\Entity\UserBaseStatistics;
use App\Enum\QuestCategory;
use App\Enum\UserStatType;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Service\Progression\DailyChallengeService;
use App\Service\Progression\QuestProgressService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SkillPointsService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private QuestProgressService $questProgressService,
        #[Autowire(lazy: true)]
        private DailyChallengeService $dailyChallengeService,
    ) {
    }

    public function addFreeSkillPoints(User $user, int $points): void
    {
        if ($points <= 0) {
            throw new BusinessRuleException('skillPointsAmountMustBePositive');
        }

        $current = $user->getFreeSkillPointsAvailable() ?? 0;
        $user->setFreeSkillPointsAvailable($current + $points);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function addSkillPoint(User $user, UserStatType $stat): void
    {
        $em = $this->entityManager;
        $connection = $em->getConnection();
        $connection->beginTransaction();
        try {
            $lockedUser = $em->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if ($lockedUser === null) {
                throw new ResourceNotFoundException('userNotFound');
            }

            $baseStats = $lockedUser->getUserBaseStatistics();
            if (!$baseStats instanceof UserBaseStatistics) {
                throw new ResourceNotFoundException('userStatisticsNotFound');
            }

            $free = $lockedUser->getFreeSkillPointsAvailable() ?? 0;
            if ($free > 0) {
                $lockedUser->setFreeSkillPointsAvailable($free - 1);
            } else {
                $this->chargeGoldForAttributePoint($lockedUser, $stat);
            }

            $this->incrementBaseStatistic($baseStats, $stat);

            $em->persist($lockedUser);
            $em->persist($baseStats);
            $em->flush();
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    private function chargeGoldForAttributePoint(User $user, UserStatType $stat): void
    {
        $priceBook = $user->getUserSkillPointsPrices();
        if (!$priceBook) {
            throw new ResourceNotFoundException('userSkillPointsPricesNotFound');
        }

        $goldCost = match ($stat) {
            UserStatType::STRENGTH => $priceBook->getStrengthPointsPrice() ?? 5,
            UserStatType::AGILITY => $priceBook->getAgilityPointsPrice() ?? 5,
            UserStatType::INTELLIGENCE => $priceBook->getIntelligencePointsPrice() ?? 5,
            UserStatType::ENDURANCE => $priceBook->getEndurancePointsPrice() ?? 5,
            UserStatType::LUCK => $priceBook->getLuckPointsPrice() ?? 5,
        };

        $user->spendGold($goldCost);

        match ($stat) {
            UserStatType::STRENGTH => $priceBook->setStrengthPointsPrice($goldCost + 1),
            UserStatType::AGILITY => $priceBook->setAgilityPointsPrice($goldCost + 1),
            UserStatType::INTELLIGENCE => $priceBook->setIntelligencePointsPrice($goldCost + 1),
            UserStatType::ENDURANCE => $priceBook->setEndurancePointsPrice($goldCost + 1),
            UserStatType::LUCK => $priceBook->setLuckPointsPrice($goldCost + 1),
        };

        $this->entityManager->persist($priceBook);

        if ($goldCost > 0) {
            $this->questProgressService->checkAndUpdateProgress($user, QuestCategory::GOLD_SPENT, $goldCost);
            $this->dailyChallengeService->recordGoldSpent($user, $goldCost);
        }
    }

    private function incrementBaseStatistic(UserBaseStatistics $stats, UserStatType $stat): void
    {
        match ($stat) {
            UserStatType::STRENGTH => $stats->setStrength(($stats->getStrength() ?? 0) + 1),
            UserStatType::AGILITY => $stats->setAgility(($stats->getAgility() ?? 0) + 1),
            UserStatType::INTELLIGENCE => $stats->setIntelligence(($stats->getIntelligence() ?? 0) + 1),
            UserStatType::ENDURANCE => $stats->setEndurance(($stats->getEndurance() ?? 0) + 1),
            UserStatType::LUCK => $stats->setLuck(($stats->getLuck() ?? 0) + 1),
        };
    }
}
