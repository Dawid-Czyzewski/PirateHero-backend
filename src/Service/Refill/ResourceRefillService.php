<?php

declare(strict_types=1);

namespace App\Service\Refill;

use App\Domain\Constants\EconomyConstants;
use App\Entity\User;
use App\Entity\UserRefill;
use App\Enum\RefillType;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Repository\UserRefillRepository;
use App\Service\Economy\BoosterService;
use App\Service\Progression\DailyChallengeService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class ResourceRefillService
{
    private const MAX_DAILY_REFILLS = 2;
    private const FIRST_REFILL_MULTIPLIER = 1;
    private const SECOND_REFILL_MULTIPLIER = 2;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly BoosterService $boosterService,
        private readonly UserRefillRepository $userRefillRepository,
        private readonly DailyChallengeService $dailyChallengeService,
    ) {
    }

    public function calculateRefillCost(User $user, int $refillNumber): int
    {
        $level = $user->getLevel();
        if (!$level) {
            throw new BusinessRuleException('userLevelNotFound');
        }

        $levelNumber = (int) $level->getName();
        $baseCost = $levelNumber * EconomyConstants::REFILL_COST_PER_LEVEL;
        $multiplier = $refillNumber === 1 ? self::FIRST_REFILL_MULTIPLIER : self::SECOND_REFILL_MULTIPLIER;

        return (int) ($baseCost * $multiplier);
    }

    /**
     * @return array<string, mixed>
     */
    public function canRefill(User $user, RefillType $type): array
    {
        $this->prepareUser($user, $type);

        $userRefill = $this->getOrCreateUserRefill($user, $type);
        $currentRefillCount = $userRefill->getRefillCount() ?? 0;
        [$current, $max] = $this->readPoints($user, $type);
        $blocked = $this->isActivityBlocked($user, $type);

        $canRefill = $currentRefillCount < self::MAX_DAILY_REFILLS && $current < $max && !$blocked;
        $nextRefillNumber = $currentRefillCount + 1;
        $cost = $canRefill ? $this->calculateRefillCost($user, $nextRefillNumber) : 0;

        return $this->buildStatusPayload($type, $canRefill, $currentRefillCount, $cost, $current, $max, $blocked);
    }

    /**
     * @return array<string, mixed>
     */
    public function refill(User $user, RefillType $type): array
    {
        $this->assertNotBlockedForRefill($user, $type);

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $lockedUser = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if ($lockedUser === null) {
                throw new ResourceNotFoundException('userNotFound');
            }

            $this->prepareUser($lockedUser, $type);

            $userRefill = $this->getOrCreateUserRefill($lockedUser, $type);
            $currentRefillCount = $userRefill->getRefillCount() ?? 0;
            [$current, $max] = $this->readPoints($lockedUser, $type);

            if ($currentRefillCount >= self::MAX_DAILY_REFILLS) {
                throw new BusinessRuleException('refillDailyLimitExhausted');
            }

            if ($current >= $max) {
                throw new BusinessRuleException($this->alreadyFullError($type));
            }

            $nextRefillNumber = $currentRefillCount + 1;
            $cost = $this->calculateRefillCost($lockedUser, $nextRefillNumber);

            $lockedUser->spendGold($cost, 'insufficientGold');
            $this->dailyChallengeService->recordGoldSpent($lockedUser, $cost);
            $this->writePoints($lockedUser, $type, $max);

            $userRefill->setRefillCount($nextRefillNumber);
            $userRefill->setLastRefillDate(new \DateTime());

            $this->entityManager->persist($lockedUser);
            $this->entityManager->persist($userRefill);
            $this->entityManager->flush();
            $connection->commit();

            return $this->buildRefillPayload($type, $max, $lockedUser->getGold(), $userRefill->getRefillCount() ?? 0, $cost);
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    private function prepareUser(User $user, RefillType $type): void
    {
        $this->resetDailyRefillsIfNeeded($user, $type);
        $this->entityManager->refresh($user);
        $this->boosterService->calculateActualCapacity($user);
        $this->entityManager->flush();
    }

    private function assertNotBlockedForRefill(User $user, RefillType $type): void
    {
        if ($type === RefillType::ENERGY && $user->getCurrentActivity() !== null) {
            throw new BusinessRuleException('refillNotAllowedDuringActiveMission');
        }

        if (
            $type === RefillType::TRAINING
            && $user->getCurrentActivity() !== null
            && $user->getCurrentActivity()->getTraining() !== null
        ) {
            throw new BusinessRuleException('refillNotAllowedDuringActiveTraining');
        }
    }

    private function isActivityBlocked(User $user, RefillType $type): bool
    {
        return match ($type) {
            RefillType::ENERGY => $user->getCurrentActivity() !== null,
            RefillType::TRAINING => $user->getCurrentActivity() !== null
                && $user->getCurrentActivity()->getTraining() !== null,
            RefillType::FIGHT => false,
        };
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function readPoints(User $user, RefillType $type): array
    {
        return match ($type) {
            RefillType::ENERGY => [
                $user->getEnergyPoints() ?? 0,
                $user->getUserCapacities()?->getEnergyPoints() ?? 100,
            ],
            RefillType::FIGHT => [
                $user->getDuelPoints() ?? 0,
                $user->getUserCapacities()?->getFightPoints() ?? 10,
            ],
            RefillType::TRAINING => [
                $user->getTrainingPoints() ?? 0,
                $user->getUserCapacities()?->getTrainingPoints() ?? 10,
            ],
        };
    }

    private function writePoints(User $user, RefillType $type, int $max): void
    {
        match ($type) {
            RefillType::ENERGY => $user->refillEnergy($max),
            RefillType::FIGHT => $user->refillDuelPoints($max),
            RefillType::TRAINING => $user->refillTrainingPoints($max),
        };
    }

    private function alreadyFullError(RefillType $type): string
    {
        return match ($type) {
            RefillType::ENERGY => 'energyAlreadyFull',
            RefillType::FIGHT => 'fightPointsAlreadyFull',
            RefillType::TRAINING => 'trainingPointsAlreadyFull',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStatusPayload(
        RefillType $type,
        bool $canRefill,
        int $currentRefillCount,
        int $cost,
        int $current,
        int $max,
        bool $blocked,
    ): array {
        $base = [
            'canRefill' => $canRefill,
            'refillsRemaining' => max(0, self::MAX_DAILY_REFILLS - $currentRefillCount),
            'refillsUsed' => $currentRefillCount,
            'nextRefillCost' => $cost,
        ];

        return match ($type) {
            RefillType::ENERGY => $base + [
                'currentEnergy' => $current,
                'maxEnergy' => $max,
                'hasActiveMission' => $blocked,
            ],
            RefillType::FIGHT => $base + [
                'maxDailyRefills' => self::MAX_DAILY_REFILLS,
                'currentFightPoints' => $current,
                'maxFightPoints' => $max,
                'hasActiveFight' => $blocked,
            ],
            RefillType::TRAINING => $base + [
                'currentTrainingPoints' => $current,
                'maxTrainingPoints' => $max,
                'hasActiveTraining' => $blocked,
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRefillPayload(
        RefillType $type,
        int $max,
        int $newGold,
        int $refillsUsed,
        int $cost,
    ): array {
        $base = [
            'success' => true,
            'newGold' => $newGold,
            'refillsUsed' => $refillsUsed,
            'refillsRemaining' => max(0, self::MAX_DAILY_REFILLS - $refillsUsed),
            'cost' => $cost,
        ];

        return match ($type) {
            RefillType::ENERGY => $base + ['newEnergy' => $max],
            RefillType::FIGHT => $base + [
                'newFightPoints' => $max,
                'maxDailyRefills' => self::MAX_DAILY_REFILLS,
            ],
            RefillType::TRAINING => $base + ['newTrainingPoints' => $max],
        };
    }

    private function resetDailyRefillsIfNeeded(User $user, RefillType $type): void
    {
        $userRefill = $this->getOrCreateUserRefill($user, $type);
        $lastRefillDate = $userRefill->getLastRefillDate();
        $now = new \DateTime();

        if ($lastRefillDate === null) {
            $userRefill->setRefillCount(0);
            $this->entityManager->persist($userRefill);
            $this->entityManager->flush();

            return;
        }

        $lastRefillDay = (clone $lastRefillDate)->setTime(0, 0, 0);
        $today = (clone $now)->setTime(0, 0, 0);

        if ($lastRefillDay < $today) {
            $userRefill->setRefillCount(0);
            $this->entityManager->persist($userRefill);
            $this->entityManager->flush();
        }
    }

    private function getOrCreateUserRefill(User $user, RefillType $type): UserRefill
    {
        $userRefill = $this->userRefillRepository->findByUserAndType($user, $type);

        if (!$userRefill) {
            $userRefill = new UserRefill();
            $userRefill->setUser($user);
            $userRefill->setType($type);
            $userRefill->setRefillCount(0);
            $userRefill->setLastRefillDate(null);
            $user->addUserRefill($userRefill);
            $this->entityManager->persist($userRefill);
            $this->entityManager->flush();
        }

        return $userRefill;
    }
}
