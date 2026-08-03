<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Entity\Mission;
use App\Entity\Training;
use App\Entity\User;
use App\Entity\Work;
use App\Exception\BusinessRuleException;

final class TimedActivityGuard
{
    public function assertNoTimedActivity(User $user): void
    {
        $activity = $user->getCurrentActivity();
        if ($activity === null) {
            return;
        }

        if ($activity->getMission() !== null) {
            throw new BusinessRuleException('finishMissionFirst');
        }

        if ($activity->getWork() !== null) {
            throw new BusinessRuleException('finishWorkFirst');
        }

        if ($activity->getTraining() !== null) {
            throw new BusinessRuleException('finishTrainingFirst');
        }
    }

    public function assertNoOtherMissionInProgress(User $user, Mission $mission): void
    {
        $currentActivity = $user->getCurrentActivity();
        if ($currentActivity === null) {
            return;
        }

        $activeMission = $currentActivity->getMission();
        if ($activeMission !== null && $activeMission->getId() !== $mission->getId()) {
            throw new BusinessRuleException('cancelCurrentMissionFirst');
        }
    }

    public function assertNoOtherWorkInProgress(User $user, Work $work): void
    {
        $currentActivity = $user->getCurrentActivity();
        if ($currentActivity === null) {
            return;
        }

        $activeWork = $currentActivity->getWork();
        if ($activeWork !== null && $activeWork->getId() !== $work->getId()) {
            throw new BusinessRuleException('cancelCurrentWorkFirst');
        }
    }

    public function assertNoOtherTrainingInProgress(User $user, Training $training): void
    {
        $currentActivity = $user->getCurrentActivity();
        if ($currentActivity === null) {
            return;
        }

        $activeTraining = $currentActivity->getTraining();
        if ($activeTraining !== null && $activeTraining->getId() !== $training->getId()) {
            throw new BusinessRuleException('cancelCurrentTrainingFirst');
        }
    }
}
