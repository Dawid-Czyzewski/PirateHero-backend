<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Domain\Constants\DailyChallengeConstants;
use App\Entity\User;
use App\Entity\UserDailyChallenge;
use App\Entity\UserDailyChallengeDay;
use App\Enum\DailyChallengeType;
use App\Exception\BusinessRuleException;
use App\Repository\UserDailyChallengeDayRepository;
use App\Repository\UserDailyChallengeRepository;
use App\Service\User\LevelService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class DailyChallengeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserDailyChallengeRepository $challengeRepository,
        private readonly UserDailyChallengeDayRepository $dayRepository,
        private readonly LevelService $levelService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(User $user): array
    {
        $today = $this->today();
        $challenges = $this->ensureTodayChallenges($user, $today);
        $day = $this->ensureDayRow($user, $today);

        return $this->buildStatusPayload($user, $challenges, $day);
    }

    /**
     * @return array<string, mixed>
     */
    public function claimSlot(User $user, int $slot): array
    {
        if ($slot < 1 || $slot > DailyChallengeConstants::SLOT_COUNT) {
            throw new BusinessRuleException('dailyChallengeInvalidSlot');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $locked = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$locked instanceof User) {
                throw new BusinessRuleException('userNotFound');
            }

            $today = $this->today();
            $this->ensureTodayChallenges($locked, $today);
            $challenge = $this->challengeRepository->findOneForUserDateSlot($locked, $today, $slot);
            if (!$challenge instanceof UserDailyChallenge) {
                throw new BusinessRuleException('dailyChallengeNotFound');
            }

            if (!$challenge->isComplete()) {
                throw new BusinessRuleException('dailyChallengeNotComplete');
            }
            if ($challenge->isRewardClaimed()) {
                throw new BusinessRuleException('dailyChallengeAlreadyClaimed');
            }

            $level = $this->resolveLevel($locked);
            $reward = DailyChallengeConstants::slotReward($level);
            $locked->addGold($reward['gold']);
            $locked->addExperiencePoints($reward['exp']);
            $challenge->setRewardClaimed(true);

            $levelData = $this->levelService->checkAndUpdateLevel($locked);
            $this->entityManager->flush();
            $connection->commit();

            $day = $this->ensureDayRow($locked, $today);
            $status = $this->buildStatusPayload(
                $locked,
                $this->challengeRepository->findForUserDate($locked, $today),
                $day,
            );

            return [
                'rewards' => $reward,
                'levelUp' => (bool) ($levelData['levelUp'] ?? false),
                'updatedUser' => $this->userSnapshot($locked),
                'status' => $status,
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function claimBonus(User $user): array
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $locked = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$locked instanceof User) {
                throw new BusinessRuleException('userNotFound');
            }

            $today = $this->today();
            $challenges = $this->ensureTodayChallenges($locked, $today);
            $day = $this->ensureDayRow($locked, $today);

            foreach ($challenges as $challenge) {
                if (!$challenge->isRewardClaimed()) {
                    throw new BusinessRuleException('dailyChallengeBonusLocked');
                }
            }
            if ($day->isBonusClaimed()) {
                throw new BusinessRuleException('dailyChallengeBonusAlreadyClaimed');
            }

            $level = $this->resolveLevel($locked);
            $reward = DailyChallengeConstants::bonusReward($level);
            $locked->addGold($reward['gold']);
            $locked->addDiamonds($reward['diamonds']);
            $day->setBonusClaimed(true);

            $this->levelService->checkAndUpdateLevel($locked);
            $this->entityManager->flush();
            $connection->commit();

            $status = $this->buildStatusPayload(
                $locked,
                $this->challengeRepository->findForUserDate($locked, $today),
                $day,
            );

            return [
                'rewards' => $reward,
                'updatedUser' => $this->userSnapshot($locked),
                'status' => $status,
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function recordMissions(User $user, int $amount = 1): void
    {
        $this->incrementType($user, DailyChallengeType::Missions, $amount);
    }

    public function recordArenaWins(User $user, int $amount = 1): void
    {
        $this->incrementType($user, DailyChallengeType::ArenaWins, $amount);
    }

    public function recordGoldSpent(User $user, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }
        $this->incrementType($user, DailyChallengeType::GoldSpent, $amount);
    }

    private function incrementType(User $user, DailyChallengeType $type, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $today = $this->today();
        $challenges = $this->ensureTodayChallenges($user, $today);
        $changed = false;
        foreach ($challenges as $challenge) {
            if ($challenge->getType() !== $type->value || $challenge->isRewardClaimed()) {
                continue;
            }
            if ($challenge->isComplete()) {
                continue;
            }
            $challenge->addProgress($amount);
            $changed = true;
        }

        if ($changed) {
            $this->entityManager->flush();
        }
    }

    /**
     * @return list<UserDailyChallenge>
     */
    private function ensureTodayChallenges(User $user, \DateTimeImmutable $today): array
    {
        $existing = $this->challengeRepository->findForUserDate($user, $today);
        if (\count($existing) === DailyChallengeConstants::SLOT_COUNT) {
            return $existing;
        }

        foreach ($existing as $row) {
            $this->entityManager->remove($row);
        }
        $this->entityManager->flush();

        $level = $this->resolveLevel($user);
        $types = [
            DailyChallengeType::Missions,
            DailyChallengeType::ArenaWins,
            DailyChallengeType::GoldSpent,
        ];

        $created = [];
        foreach ($types as $index => $type) {
            $challenge = new UserDailyChallenge();
            $challenge->setUser($user);
            $challenge->setChallengeDate($today);
            $challenge->setSlot($index + 1);
            $challenge->setType($type->value);
            $challenge->setTargetValue(DailyChallengeConstants::targetForType($type->value, $level));
            $challenge->setProgress(0);
            $challenge->setRewardClaimed(false);
            $this->entityManager->persist($challenge);
            $created[] = $challenge;
        }

        $this->ensureDayRow($user, $today);
        $this->entityManager->flush();

        return $created;
    }

    private function ensureDayRow(User $user, \DateTimeImmutable $today): UserDailyChallengeDay
    {
        $day = $this->dayRepository->findOneForUserDate($user, $today);
        if ($day instanceof UserDailyChallengeDay) {
            return $day;
        }

        $day = new UserDailyChallengeDay();
        $day->setUser($user);
        $day->setChallengeDate($today);
        $day->setBonusClaimed(false);
        $this->entityManager->persist($day);
        $this->entityManager->flush();

        return $day;
    }

    /**
     * @param list<UserDailyChallenge> $challenges
     *
     * @return array<string, mixed>
     */
    private function buildStatusPayload(User $user, array $challenges, UserDailyChallengeDay $day): array
    {
        $level = $this->resolveLevel($user);
        $items = [];
        $unclaimed = 0;
        $allSlotRewardsClaimed = true;

        foreach ($challenges as $challenge) {
            $complete = $challenge->isComplete();
            $claimed = $challenge->isRewardClaimed();
            if ($complete && !$claimed) {
                ++$unclaimed;
            }
            if (!$claimed) {
                $allSlotRewardsClaimed = false;
            }

            $slotReward = DailyChallengeConstants::slotReward($level);
            $items[] = [
                'slot' => $challenge->getSlot(),
                'type' => $challenge->getType(),
                'targetValue' => $challenge->getTargetValue(),
                'progress' => min($challenge->getProgress(), $challenge->getTargetValue()),
                'complete' => $complete,
                'rewardClaimed' => $claimed,
                'canClaim' => $complete && !$claimed,
                'rewards' => $slotReward,
            ];
        }

        $bonus = DailyChallengeConstants::bonusReward($level);
        $bonusAvailable = $allSlotRewardsClaimed && !$day->isBonusClaimed() && \count($challenges) === DailyChallengeConstants::SLOT_COUNT;
        if ($bonusAvailable) {
            ++$unclaimed;
        }

        $dateLabel = $this->today()->format('Y-m-d');

        return [
            'date' => $dateLabel,
            'challenges' => $items,
            'bonus' => [
                'rewards' => $bonus,
                'claimed' => $day->isBonusClaimed(),
                'canClaim' => $bonusAvailable,
            ],
            'unclaimedCount' => $unclaimed,
        ];
    }

    private function today(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('today');
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
