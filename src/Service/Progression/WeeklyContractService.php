<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Domain\Constants\WeeklyContractConstants;
use App\Entity\User;
use App\Entity\UserWeeklyContract;
use App\Enum\WeeklyContractType;
use App\Exception\BusinessRuleException;
use App\Repository\UserWeeklyContractRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class WeeklyContractService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserWeeklyContractRepository $contractRepository,
        private readonly TitleService $titleService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(User $user): array
    {
        $contract = $this->ensureWeek($user);

        return $this->buildStatusPayload($user, $contract);
    }

    /**
     * @return array<string, mixed>
     */
    public function claim(User $user): array
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $locked = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$locked instanceof User) {
                throw new BusinessRuleException('userNotFound');
            }

            $weekStart = $this->weekStart();
            $contract = $this->contractRepository->findOneForUserWeek($locked, $weekStart);
            if (!$contract instanceof UserWeeklyContract) {
                $contract = $this->ensureWeek($locked);
            }

            if (!$contract->isComplete()) {
                throw new BusinessRuleException('weeklyContractNotComplete');
            }
            if ($contract->isRewardClaimed()) {
                throw new BusinessRuleException('weeklyContractAlreadyClaimed');
            }

            $level = $this->resolveLevel($locked);
            $reward = WeeklyContractConstants::claimReward($level);
            $locked->addGold($reward['gold']);
            $locked->addDiamonds($reward['diamonds']);
            $contract->setRewardClaimed(true);

            $titleGranted = $this->titleService->grantTitle($locked, TitleCodes::WEEKLY_CORSAIR);

            $this->entityManager->persist($locked);
            $this->entityManager->persist($contract);
            $this->entityManager->flush();
            $connection->commit();

            return [
                'rewards' => $reward,
                'titleGranted' => $titleGranted,
                'titleCode' => $titleGranted ? TitleCodes::WEEKLY_CORSAIR : null,
                'updatedUser' => $this->userSnapshot($locked),
                'status' => $this->buildStatusPayload($locked, $contract),
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function recordMissions(User $user, int $amount = 1): void
    {
        $this->incrementIfType($user, WeeklyContractType::Missions, $amount);
    }

    public function recordArenaWins(User $user, int $amount = 1): void
    {
        $this->incrementIfType($user, WeeklyContractType::ArenaWins, $amount);
    }

    public function recordGoldSpent(User $user, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }
        $this->incrementIfType($user, WeeklyContractType::GoldSpent, $amount);
    }

    private function incrementIfType(User $user, WeeklyContractType $type, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $contract = $this->ensureWeek($user);
        if ($contract->getType() !== $type->value || $contract->isRewardClaimed() || $contract->isComplete()) {
            return;
        }

        $contract->addProgress($amount);
        $this->entityManager->flush();
    }

    private function ensureWeek(User $user): UserWeeklyContract
    {
        $weekStart = $this->weekStart();
        $existing = $this->contractRepository->findOneForUserWeek($user, $weekStart);
        if ($existing instanceof UserWeeklyContract) {
            return $existing;
        }

        $level = $this->resolveLevel($user);
        $weekNumber = (int) $weekStart->format('oW');
        $type = WeeklyContractConstants::typeForWeekNumber($weekNumber);

        $contract = new UserWeeklyContract();
        $contract->setUser($user);
        $contract->setWeekStart($weekStart);
        $contract->setType($type);
        $contract->setTargetValue(WeeklyContractConstants::targetForType($type, $level));
        $contract->setProgress(0);
        $contract->setRewardClaimed(false);
        $this->entityManager->persist($contract);
        $this->entityManager->flush();

        return $contract;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStatusPayload(User $user, UserWeeklyContract $contract): array
    {
        $level = $this->resolveLevel($user);
        $complete = $contract->isComplete();
        $claimed = $contract->isRewardClaimed();
        $canClaim = $complete && !$claimed;
        $reward = WeeklyContractConstants::claimReward($level);
        $weekEnd = $contract->getWeekStart()->modify('+6 days');

        return [
            'weekStart' => $contract->getWeekStart()->format('Y-m-d'),
            'weekEnd' => $weekEnd->format('Y-m-d'),
            'type' => $contract->getType(),
            'targetValue' => $contract->getTargetValue(),
            'progress' => min($contract->getProgress(), $contract->getTargetValue()),
            'complete' => $complete,
            'rewardClaimed' => $claimed,
            'canClaim' => $canClaim,
            'rewards' => $reward,
            'titleRewardCode' => TitleCodes::WEEKLY_CORSAIR,
            'unclaimedCount' => $canClaim ? 1 : 0,
        ];
    }

    private function weekStart(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('monday this week');
    }

    private function resolveLevel(User $user): int
    {
        $level = (int) ($user->getLevel()?->getName() ?? '1');

        return $level > 0 ? $level : 1;
    }

    /**
     * @return array<string, mixed>
     */
    private function userSnapshot(User $user): array
    {
        $level = $user->getLevel();

        return [
            'gold' => (int) $user->getGold(),
            'diamonds' => (int) ($user->getDiamonds() ?? 0),
            'experiencePoints' => (int) $user->getExperiencePoints(),
            'freeSkillPointsAvailable' => (int) ($user->getFreeSkillPointsAvailable() ?? 0),
            'level' => [
                'name' => (string) ($level?->getName() ?? '1'),
                'expToNextLevel' => (int) ($level?->getExpToNextLevel() ?? 100),
            ],
        ];
    }
}
