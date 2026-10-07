<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Domain\Constants\WeekendTournamentConstants;
use App\Entity\User;
use App\Entity\UserWeekendTournament;
use App\Exception\BusinessRuleException;
use App\Repository\UserWeekendTournamentRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class WeekendTournamentService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserWeekendTournamentRepository $repository,
        private readonly TitleService $titleService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(User $user): array
    {
        $eventStart = $this->currentOrLastEventStart();
        $row = $this->ensureEvent($user, $eventStart);

        return $this->buildStatusPayload($user, $row, $eventStart);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getLeaderboard(): array
    {
        $eventStart = $this->currentOrLastEventStart();
        $rows = $this->repository->findTopForEvent($eventStart, WeekendTournamentConstants::LEADERBOARD_LIMIT);
        $out = [];
        $rank = 1;
        foreach ($rows as $row) {
            $u = $row->getUser();
            $out[] = [
                'rank' => $rank,
                'userId' => $u?->getId(),
                'username' => $u?->getUsername(),
                'points' => $row->getPoints(),
            ];
            ++$rank;
        }

        return $out;
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

            $eventStart = $this->currentOrLastEventStart();
            if ($this->isWeekendOpenNow()) {
                throw new BusinessRuleException('weekendTournamentStillActive');
            }

            $row = $this->repository->findOneForUserEvent($locked, $eventStart);
            if (!$row instanceof UserWeekendTournament) {
                $row = $this->ensureEvent($locked, $eventStart);
            }

            if ($row->getPoints() < WeekendTournamentConstants::MIN_POINTS_TO_CLAIM) {
                throw new BusinessRuleException('weekendTournamentNotEnoughPoints');
            }
            if ($row->isRewardClaimed()) {
                throw new BusinessRuleException('weekendTournamentAlreadyClaimed');
            }

            $rank = $this->resolveRank($row);
            $level = $this->resolveLevel($locked);
            $reward = WeekendTournamentConstants::claimReward($rank, $level);
            $locked->addGold($reward['gold']);
            $locked->addDiamonds($reward['diamonds']);
            $row->setRewardClaimed(true);

            $titleCode = WeekendTournamentConstants::titleCodeForClaim($rank);
            $titleGranted = $this->titleService->grantTitle($locked, $titleCode);

            $this->entityManager->persist($locked);
            $this->entityManager->persist($row);
            $this->entityManager->flush();
            $connection->commit();

            return [
                'rank' => $rank,
                'rewards' => $reward,
                'titleGranted' => $titleGranted,
                'titleCode' => $titleGranted ? $titleCode : null,
                'updatedUser' => $this->userSnapshot($locked),
                'status' => $this->buildStatusPayload($locked, $row, $eventStart),
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function recordWin(User $user, int $points = 1, ?\DateTimeImmutable $now = null): void
    {
        $now ??= new \DateTimeImmutable('now');
        if ($points <= 0 || !$this->isWeekendOpenNow($now)) {
            return;
        }

        $eventStart = $this->saturdayThisWeek($now);
        $row = $this->ensureEvent($user, $eventStart);
        $row->addPoints($points);
        $this->entityManager->flush();
    }

    public function isWeekendOpenNow(?\DateTimeImmutable $now = null): bool
    {
        $now ??= new \DateTimeImmutable('now');
        $dow = (int) $now->format('N'); // 6=Sat, 7=Sun

        return $dow === 6 || $dow === 7;
    }

    private function ensureEvent(User $user, \DateTimeImmutable $eventStart): UserWeekendTournament
    {
        $existing = $this->repository->findOneForUserEvent($user, $eventStart);
        if ($existing instanceof UserWeekendTournament) {
            return $existing;
        }

        $row = new UserWeekendTournament();
        $row->setUser($user);
        $row->setEventStart($eventStart);
        $row->setPoints(0);
        $row->setRewardClaimed(false);
        $this->entityManager->persist($row);
        $this->entityManager->flush();

        return $row;
    }

    private function currentOrLastEventStart(?\DateTimeImmutable $now = null): \DateTimeImmutable
    {
        $now ??= new \DateTimeImmutable('now');
        if ($this->isWeekendOpenNow($now)) {
            return $this->saturdayThisWeek($now);
        }

        $mutable = \DateTime::createFromImmutable($now);
        $mutable->modify('saturday last week');
        $mutable->setTime(0, 0);

        return \DateTimeImmutable::createFromMutable($mutable);
    }

    private function saturdayThisWeek(?\DateTimeImmutable $now = null): \DateTimeImmutable
    {
        $now ??= new \DateTimeImmutable('now');
        $mutable = \DateTime::createFromImmutable($now);
        $mutable->modify('saturday this week');
        $mutable->setTime(0, 0);

        return \DateTimeImmutable::createFromMutable($mutable);
    }

    private function resolveRank(UserWeekendTournament $row): ?int
    {
        if ($row->getPoints() <= 0 || $row->getId() === null) {
            return null;
        }

        $better = $this->repository->countPlayersWithMorePoints(
            $row->getEventStart(),
            $row->getPoints(),
            $row->getId(),
        );

        return $better + 1;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStatusPayload(User $user, UserWeekendTournament $row, \DateTimeImmutable $eventStart): array
    {
        $active = $this->isWeekendOpenNow();
        $eventEnd = $eventStart->modify('+1 day');
        $rank = $row->getPoints() > 0 ? $this->resolveRank($row) : null;
        $level = $this->resolveLevel($user);
        $canClaim = !$active
            && !$row->isRewardClaimed()
            && $row->getPoints() >= WeekendTournamentConstants::MIN_POINTS_TO_CLAIM;
        $previewReward = WeekendTournamentConstants::claimReward($rank, $level);
        $leaderboard = [];
        foreach ($this->repository->findTopForEvent($eventStart, WeekendTournamentConstants::LEADERBOARD_LIMIT) as $i => $top) {
            $leaderboard[] = [
                'rank' => $i + 1,
                'userId' => $top->getUser()?->getId(),
                'username' => $top->getUser()?->getUsername(),
                'points' => $top->getPoints(),
            ];
        }

        return [
            'active' => $active,
            'eventStart' => $eventStart->format('Y-m-d'),
            'eventEnd' => $eventEnd->format('Y-m-d'),
            'points' => $row->getPoints(),
            'rank' => $rank,
            'rewardClaimed' => $row->isRewardClaimed(),
            'canClaim' => $canClaim,
            'minPointsToClaim' => WeekendTournamentConstants::MIN_POINTS_TO_CLAIM,
            'rewards' => $previewReward,
            'leaderboard' => $leaderboard,
            'unclaimedCount' => $canClaim ? 1 : 0,
        ];
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
