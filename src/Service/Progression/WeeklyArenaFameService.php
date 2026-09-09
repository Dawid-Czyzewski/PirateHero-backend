<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Domain\Constants\WeeklyArenaFameConstants;
use App\Entity\User;
use App\Entity\UserWeeklyArenaFame;
use App\Exception\BusinessRuleException;
use App\Repository\UserWeeklyArenaFameRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class WeeklyArenaFameService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserWeeklyArenaFameRepository $repository,
        private readonly TitleService $titleService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(User $user): array
    {
        $row = $this->ensureWeek($user);

        return $this->buildStatusPayload($user, $row);
    }

    /**
     * @return array<string, mixed>
     */
    public function claimTier(User $user, int $tier): array
    {
        if ($tier < 1 || $tier > 3) {
            throw new BusinessRuleException('weeklyArenaFameInvalidTier');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $locked = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$locked instanceof User) {
                throw new BusinessRuleException('userNotFound');
            }

            $weekStart = $this->weekStart();
            $row = $this->repository->findOneForUserWeek($locked, $weekStart);
            if (!$row instanceof UserWeeklyArenaFame) {
                $row = $this->ensureWeek($locked);
            }

            $threshold = WeeklyArenaFameConstants::thresholdForTier($tier);
            if ($row->getFameEarned() < $threshold) {
                throw new BusinessRuleException('weeklyArenaFameTierNotReached');
            }
            if ($row->isClaimedTier($tier)) {
                throw new BusinessRuleException('weeklyArenaFameTierAlreadyClaimed');
            }

            $level = $this->resolveLevel($locked);
            $reward = WeeklyArenaFameConstants::claimReward($tier, $level);
            $locked->addGold($reward['gold']);
            $locked->addDiamonds($reward['diamonds']);
            $row->setClaimedTier($tier, true);

            $titleCode = WeeklyArenaFameConstants::titleCodeForTier($tier);
            $titleGranted = $this->titleService->grantTitle($locked, $titleCode);

            $this->entityManager->persist($locked);
            $this->entityManager->persist($row);
            $this->entityManager->flush();
            $connection->commit();

            return [
                'tier' => $tier,
                'rewards' => $reward,
                'titleGranted' => $titleGranted,
                'titleCode' => $titleGranted ? $titleCode : null,
                'updatedUser' => $this->userSnapshot($locked),
                'status' => $this->buildStatusPayload($locked, $row),
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function recordFameGained(User $user, int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $row = $this->ensureWeek($user);
        $row->addFameEarned($amount);
        $this->entityManager->flush();
    }

    private function ensureWeek(User $user): UserWeeklyArenaFame
    {
        $weekStart = $this->weekStart();
        $existing = $this->repository->findOneForUserWeek($user, $weekStart);
        if ($existing instanceof UserWeeklyArenaFame) {
            return $existing;
        }

        $row = new UserWeeklyArenaFame();
        $row->setUser($user);
        $row->setWeekStart($weekStart);
        $row->setFameEarned(0);
        $this->entityManager->persist($row);
        $this->entityManager->flush();

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStatusPayload(User $user, UserWeeklyArenaFame $row): array
    {
        $level = $this->resolveLevel($user);
        $fame = $row->getFameEarned();
        $weekEnd = $row->getWeekStart()->modify('+6 days');
        $tiers = [];
        $unclaimed = 0;

        for ($tier = 1; $tier <= 3; ++$tier) {
            $threshold = WeeklyArenaFameConstants::thresholdForTier($tier);
            $claimed = $row->isClaimedTier($tier);
            $canClaim = !$claimed && $fame >= $threshold;
            if ($canClaim) {
                ++$unclaimed;
            }
            $tiers[] = [
                'tier' => $tier,
                'threshold' => $threshold,
                'claimed' => $claimed,
                'canClaim' => $canClaim,
                'rewards' => WeeklyArenaFameConstants::claimReward($tier, $level),
                'titleCode' => WeeklyArenaFameConstants::titleCodeForTier($tier),
            ];
        }

        return [
            'weekStart' => $row->getWeekStart()->format('Y-m-d'),
            'weekEnd' => $weekEnd->format('Y-m-d'),
            'fameEarned' => $fame,
            'tiers' => $tiers,
            'unclaimedCount' => $unclaimed,
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
