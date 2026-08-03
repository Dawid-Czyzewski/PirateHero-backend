<?php

declare(strict_types=1);

namespace App\Service\Progression\TimedActivity;

use App\Entity\Mission;
use App\Entity\Training;
use App\Entity\User;
use App\Entity\UserActualActivity;
use App\Entity\Work;
use App\Exception\BusinessRuleException;
use Doctrine\ORM\EntityManagerInterface;

final class TimedActivityLifecycle
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function assertNoActivityInProgress(User $user): void
    {
        if ($user->getCurrentActivity() !== null) {
            throw new BusinessRuleException('activityAlreadyInProgress');
        }
    }

    public function startMission(User $user, Mission $mission): UserActualActivity
    {
        return $this->start($user, TimedActivityType::Mission, $mission);
    }

    public function startWork(User $user, Work $work): UserActualActivity
    {
        return $this->start($user, TimedActivityType::Work, $work);
    }

    public function startTraining(User $user, Training $training): UserActualActivity
    {
        return $this->start($user, TimedActivityType::Training, $training);
    }

    public function clear(User $user, UserActualActivity $activity): void
    {
        $user->setCurrentActivity(null);
        $this->entityManager->remove($activity);
    }

    public function assertElapsed(\DateTimeInterface $startTime, int $durationSeconds, string $errorKey): void
    {
        $endTime = \DateTime::createFromInterface($startTime)->modify("+{$durationSeconds} seconds");
        if (new \DateTime() < $endTime) {
            throw new BusinessRuleException($errorKey);
        }
    }

    public function assertElapsedHours(\DateTimeInterface $startTime, int $hours, string $errorKey): void
    {
        $endTime = \DateTime::createFromInterface($startTime)->modify("+{$hours} hours");
        if (new \DateTime() < $endTime) {
            throw new BusinessRuleException($errorKey);
        }
    }

    /**
     * @return array{0: UserActualActivity, 1: Mission}
     */
    public function requireActiveMission(User $user): array
    {
        $activity = $this->requireActivity($user);
        $mission = $activity->getMission();
        if ($mission === null) {
            throw new BusinessRuleException('noMissionForActivity');
        }

        return [$activity, $mission];
    }

    /**
     * @return array{0: UserActualActivity, 1: Work}
     */
    public function requireActiveWork(User $user): array
    {
        $activity = $this->requireActivity($user);
        $work = $activity->getWork();
        if ($work === null) {
            throw new BusinessRuleException('noWorkForActivity');
        }

        return [$activity, $work];
    }

    /**
     * @return array{0: UserActualActivity, 1: Training}
     */
    public function requireActiveTraining(User $user): array
    {
        $activity = $this->requireActivity($user);
        $training = $activity->getTraining();
        if ($training === null) {
            throw new BusinessRuleException('noTrainingForActivity');
        }

        return [$activity, $training];
    }

    private function requireActivity(User $user): UserActualActivity
    {
        $activity = $user->getCurrentActivity();
        if ($activity === null) {
            throw new BusinessRuleException('noActiveActivity');
        }

        return $activity;
    }

    private function start(User $user, TimedActivityType $type, Mission|Work|Training $subject): UserActualActivity
    {
        $this->assertNoActivityInProgress($user);

        $activity = new UserActualActivity();
        $activity->setUser($user);
        $activity->setStartTime(new \DateTime());

        match ($type) {
            TimedActivityType::Mission => $activity->setMission($subject instanceof Mission ? $subject : null),
            TimedActivityType::Work => $activity->setWork($subject instanceof Work ? $subject : null),
            TimedActivityType::Training => $activity->setTraining($subject instanceof Training ? $subject : null),
        };

        $user->setCurrentActivity($activity);
        $this->entityManager->persist($activity);

        return $activity;
    }
}
