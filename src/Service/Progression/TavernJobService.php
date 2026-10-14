<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Domain\Constants\TavernJobConstants;
use App\Entity\TavernJob;
use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Repository\TavernJobRepository;
use App\Service\Progression\TimedActivity\TimedActivityLifecycle;
use Doctrine\ORM\EntityManagerInterface;

class TavernJobService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TavernJobRepository $tavernJobRepository,
        private readonly UserWriteLockExecutor $userWriteLockExecutor,
        private readonly TimedActivityLifecycle $timedActivityLifecycle,
    ) {
    }

    public function ensureDailyOffers(User $user): void
    {
        $today = new \DateTimeImmutable('today');
        $existing = $this->tavernJobRepository->findForUserOnDate($user, $today);
        if ($existing !== []) {
            return;
        }

        $level = UserLevelResolver::of($user);
        foreach (TavernJobConstants::OFFER_DEFINITIONS as $def) {
            $job = new TavernJob();
            $job->setUser($user);
            $job->setTitleKey($def['titleKey']);
            $job->setDurationMinutes($def['durationMinutes']);
            $job->setRewardGold(TavernJobConstants::rewardGold($def['baseGold'], $level));
            $job->setRewardDiamonds($def['rewardDiamonds']);
            $job->setOfferedOn($today);
            $this->entityManager->persist($job);
        }

        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(User $user): array
    {
        $this->ensureDailyOffers($user);

        return $this->buildStatusPayload($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function start(User $user, int $jobId): array
    {
        return $this->userWriteLockExecutor->execute($user, function (User $lockedUser) use ($jobId): array {
            $this->ensureDailyOffers($lockedUser);
            $job = $this->resolveOwnedJob($lockedUser, $jobId);

            if ($job->isCompleted()) {
                throw new BusinessRuleException('tavernJobAlreadyCompleted');
            }
            if ($job->isStarted()) {
                throw new BusinessRuleException('tavernJobAlreadyStarted');
            }

            $this->timedActivityLifecycle->startTavernJob($lockedUser, $job);
            $job->setStarted(true);

            $this->entityManager->persist($job);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();

            return $this->buildStatusPayload($lockedUser);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function complete(User $user): array
    {
        return $this->userWriteLockExecutor->execute($user, function (User $lockedUser): array {
            [$activity, $job] = $this->timedActivityLifecycle->requireActiveTavernJob($lockedUser);

            $this->timedActivityLifecycle->assertElapsed(
                $activity->getStartTime(),
                $job->getDurationMinutes() * 60,
                'tavernJobNotComplete',
            );

            $rewardGold = $job->getRewardGold();
            $rewardDiamonds = $job->getRewardDiamonds();
            $lockedUser->addGold($rewardGold);
            if ($rewardDiamonds > 0) {
                $lockedUser->addDiamonds($rewardDiamonds);
            }

            $job->setCompleted(true);
            $this->timedActivityLifecycle->clear($lockedUser, $activity);

            $this->entityManager->persist($job);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();

            return [
                'rewardGold' => $rewardGold,
                'rewardDiamonds' => $rewardDiamonds,
                'status' => $this->buildStatusPayload($lockedUser),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function cancel(User $user): array
    {
        return $this->userWriteLockExecutor->execute($user, function (User $lockedUser): array {
            [$activity, $job] = $this->timedActivityLifecycle->requireActiveTavernJob($lockedUser);

            $this->timedActivityLifecycle->clear($lockedUser, $activity);
            $job->setStarted(false);

            $this->entityManager->persist($job);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();

            return $this->buildStatusPayload($lockedUser);
        });
    }

    private function resolveOwnedJob(User $user, int $jobId): TavernJob
    {
        $job = $this->tavernJobRepository->find($jobId);
        if (!$job instanceof TavernJob) {
            throw new ResourceNotFoundException('tavernJobNotFound');
        }
        if ($job->getUser()?->getId() !== $user->getId()) {
            throw new OperationForbiddenException('tavernJobNotYours');
        }

        return $job;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStatusPayload(User $user): array
    {
        $today = new \DateTimeImmutable('today');
        $jobs = $this->tavernJobRepository->findForUserOnDate($user, $today);

        $offers = array_map(static fn (TavernJob $job): array => [
            'id' => $job->getId(),
            'titleKey' => $job->getTitleKey(),
            'durationMinutes' => $job->getDurationMinutes(),
            'rewardGold' => $job->getRewardGold(),
            'rewardDiamonds' => $job->getRewardDiamonds(),
            'started' => $job->isStarted(),
            'completed' => $job->isCompleted(),
        ], $jobs);

        $activeJob = null;
        $unclaimedCount = 0;
        $activity = $user->getCurrentActivity();
        $activeEntity = $activity?->getTavernJob();
        if ($activity !== null && $activeEntity instanceof TavernJob) {
            $startedAt = $activity->getStartTime();
            $durationSeconds = $activeEntity->getDurationMinutes() * 60;
            $endsAt = $startedAt !== null
                ? (clone \DateTime::createFromInterface($startedAt))->modify('+'.$durationSeconds.' seconds')
                : null;
            $remaining = 0;
            if ($endsAt !== null) {
                $remaining = max(0, $endsAt->getTimestamp() - (new \DateTime())->getTimestamp());
            }
            $readyToComplete = $remaining <= 0 && !$activeEntity->isCompleted();
            if ($readyToComplete) {
                $unclaimedCount = 1;
            }

            $activeJob = [
                'id' => $activeEntity->getId(),
                'titleKey' => $activeEntity->getTitleKey(),
                'durationMinutes' => $activeEntity->getDurationMinutes(),
                'rewardGold' => $activeEntity->getRewardGold(),
                'rewardDiamonds' => $activeEntity->getRewardDiamonds(),
                'startedAt' => $startedAt?->format(\DateTimeInterface::ATOM),
                'endsAt' => $endsAt?->format(\DateTimeInterface::ATOM),
                'remainingSeconds' => $remaining,
                'readyToComplete' => $readyToComplete,
            ];
        }

        return [
            'offers' => $offers,
            'activeJob' => $activeJob,
            'unclaimedCount' => $unclaimedCount,
        ];
    }
}
