<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Entity\Mission;
use App\Entity\Training;
use App\Entity\User;
use App\Entity\UserActualActivity;
use App\Entity\Work;
use App\Exception\BusinessRuleException;
use App\Service\Progression\TimedActivityGuard;
use PHPUnit\Framework\TestCase;

final class TimedActivityGuardTest extends TestCase
{
    public function testAllowsWhenNoActivity(): void
    {
        $guard = new TimedActivityGuard();
        $guard->assertNoTimedActivity(new User());
        self::assertTrue(true);
    }

    public function testBlocksDuringMission(): void
    {
        $user = $this->userWithActivity(mission: new Mission());

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('finishMissionFirst');

        (new TimedActivityGuard())->assertNoTimedActivity($user);
    }

    public function testBlocksDuringWork(): void
    {
        $user = $this->userWithActivity(work: new Work());

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('finishWorkFirst');

        (new TimedActivityGuard())->assertNoTimedActivity($user);
    }

    public function testBlocksDuringTraining(): void
    {
        $user = $this->userWithActivity(training: new Training());

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('finishTrainingFirst');

        (new TimedActivityGuard())->assertNoTimedActivity($user);
    }

    public function testAllowsRestartingSameMission(): void
    {
        $mission = new Mission();
        $this->setEntityId($mission, 7);
        $user = $this->userWithActivity(mission: $mission);

        (new TimedActivityGuard())->assertNoOtherMissionInProgress($user, $mission);
        self::assertTrue(true);
    }

    public function testBlocksOtherMissionInProgress(): void
    {
        $active = new Mission();
        $this->setEntityId($active, 1);
        $other = new Mission();
        $this->setEntityId($other, 2);
        $user = $this->userWithActivity(mission: $active);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('cancelCurrentMissionFirst');

        (new TimedActivityGuard())->assertNoOtherMissionInProgress($user, $other);
    }

    public function testBlocksOtherTrainingInProgress(): void
    {
        $active = new Training();
        $this->setEntityId($active, 1);
        $other = new Training();
        $this->setEntityId($other, 2);
        $user = $this->userWithActivity(training: $active);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('cancelCurrentTrainingFirst');

        (new TimedActivityGuard())->assertNoOtherTrainingInProgress($user, $other);
    }

    private function setEntityId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }

    private function userWithActivity(
        ?Mission $mission = null,
        ?Work $work = null,
        ?Training $training = null,
    ): User {
        $activity = new UserActualActivity();
        $activity->setMission($mission);
        $activity->setWork($work);
        $activity->setTraining($training);

        $user = new User();
        $user->setCurrentActivity($activity);

        return $user;
    }
}
