<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Domain\Constants\ProgressionConstants;
use App\Dto\Api\Training\TrainingDto;
use App\Entity\Training;
use App\Entity\User;
use App\Enum\UserStatType;
use App\Exception\OperationForbiddenException;
use App\Mapper\Api\TrainingMapper;
use App\Service\Progression\TimedActivity\OwnedTimedActivityResolver;
use App\Service\Progression\TimedActivity\TimedActivityLifecycle;
use Doctrine\ORM\EntityManagerInterface;

class TrainingService
{
    /** @var array<string, list<string>> */
    private array $titles = [];

    /** @var array<string, list<string>> */
    private array $descriptions = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserWriteLockExecutor $userWriteLockExecutor,
        private readonly TimedActivityLifecycle $timedActivityLifecycle,
        private readonly OwnedTimedActivityResolver $ownedTimedActivityResolver,
        private readonly TrainingRewardCalculator $trainingRewardCalculator,
    ) {
        foreach (UserStatType::cases() as $statType) {
            $type = $statType->value;
            $this->titles[$type] = [
                "trainings.{$type}_1",
                "trainings.{$type}_2",
                "trainings.{$type}_3",
            ];
            $this->descriptions[$type] = [
                "trainings.{$type}_1_description",
                "trainings.{$type}_2_description",
                "trainings.{$type}_3_description",
            ];
        }
    }

    public function generateTrainingsForUser(User $user): void
    {
        foreach (UserStatType::cases() as $statType) {
            $training = new Training();
            $typeValue = $statType->value;

            $titlesForType = $this->titles[$typeValue] ?? [];
            $descriptionsForType = $this->descriptions[$typeValue] ?? [];

            $titleIndex = array_rand($titlesForType ?: [0]);
            $descIndex = array_rand($descriptionsForType ?: [0]);

            $training->setTitle($titlesForType[$titleIndex] ?? "trainings.{$typeValue}_".($titleIndex + 1));
            $training->setDescription($descriptionsForType[$descIndex] ?? "trainings.{$typeValue}_".($descIndex + 1).'_description');

            $training->setDurationInSeconds(ProgressionConstants::TRAINING_DURATION_SECONDS);
            $training->setTrainingPointsCost(ProgressionConstants::TRAINING_COST);
            $training->setSkillPointsReward(ProgressionConstants::TRAINING_SKILL_POINTS_REWARD);
            $training->setStatType($statType);
            $training->setUser($user);

            $user->addTraining($training);
            $this->entityManager->persist($training);
        }

        $this->entityManager->flush();
    }

    public function resolveOwnedTraining(User $user, int $id): Training
    {
        return $this->ownedTimedActivityResolver->resolveTraining($user, $id);
    }

    public function startTraining(User $user, Training $training): void
    {
        if ($training->getUser()?->getId() !== $user->getId()) {
            throw new OperationForbiddenException('trainingOwnershipRequired');
        }

        $this->userWriteLockExecutor->execute($user, function (User $lockedUser) use ($training): void {
            $lockedUser->spendTrainingPoints($training->getTrainingPointsCost());
            $this->timedActivityLifecycle->startTraining($lockedUser, $training);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();
        });
    }

    public function cancelTraining(User $user): void
    {
        $this->userWriteLockExecutor->execute($user, function (User $lockedUser): void {
            [$currentActivity, $training] = $this->timedActivityLifecycle->requireActiveTraining($lockedUser);
            $lockedUser->restoreTrainingPoints($training->getTrainingPointsCost());
            $this->timedActivityLifecycle->clear($lockedUser, $currentActivity);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();
        });
    }

    public function completeTraining(User $user): void
    {
        $this->userWriteLockExecutor->execute($user, function (User $lockedUser): void {
            [$currentActivity, $training] = $this->timedActivityLifecycle->requireActiveTraining($lockedUser);

            $this->timedActivityLifecycle->assertElapsed(
                $currentActivity->getStartTime(),
                (int) $training->getDurationInSeconds(),
                'trainingNotComplete',
            );

            $userStats = $this->trainingRewardCalculator->requireBaseStatistics($lockedUser);
            $this->trainingRewardCalculator->apply($userStats, $training);

            $this->timedActivityLifecycle->clear($lockedUser, $currentActivity);

            $this->entityManager->persist($lockedUser);
            $this->entityManager->persist($userStats);
            $this->entityManager->flush();

            $this->regenerateTrainingsForUser($lockedUser);
        });
    }

    public function regenerateTrainingsForUser(User $user): void
    {
        foreach ($user->getTrainings() as $training) {
            $this->entityManager->remove($training);
        }

        $user->getTrainings()->clear();

        $this->generateTrainingsForUser($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @return list<TrainingDto>
     */
    public function formatTrainingDtosForUser(User $user): array
    {
        return array_map(
            static fn (Training $training): TrainingDto => TrainingMapper::fromTraining($training),
            $user->getTrainings()->toArray()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function formatTrainingsForUser(User $user): array
    {
        return array_map(
            static fn (TrainingDto $dto) => $dto->toArray(),
            $this->formatTrainingDtosForUser($user)
        );
    }
}
