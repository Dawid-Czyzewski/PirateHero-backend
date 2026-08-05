<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Domain\Constants\MissionConstants;
use App\Dto\Api\Mission\MissionDto;
use App\Entity\Mission;
use App\Entity\Ship;
use App\Entity\User;
use App\Entity\UserActualActivity;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Mapper\Api\MissionMapper;
use App\Service\Progression\TimedActivity\OwnedTimedActivityResolver;
use App\Service\Progression\TimedActivity\TimedActivityLifecycle;
use App\Service\Ship\ShipMembershipService;
use App\Service\User\LevelService;
use Doctrine\ORM\EntityManagerInterface;

readonly class MissionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LevelService $levelService,
        private readonly ShipMembershipService $shipMembershipService,
        private readonly MissionRewardCalculator $missionRewardCalculator,
        private readonly MissionEconomyRoller $missionEconomyRoller,
        private readonly UserWriteLockExecutor $userWriteLockExecutor,
        private readonly TimedActivityLifecycle $timedActivityLifecycle,
        private readonly OwnedTimedActivityResolver $ownedTimedActivityResolver,
    ) {
    }

    public function generateMissionsForUser(User $user): void
    {
        $titles = MissionTitlePool::pickOffers();
        $playerLevel = UserLevelResolver::of($user);

        foreach (\App\Domain\Constants\MissionConstants::DURATION_SECONDS as $index => $durationSeconds) {
            $mission = new Mission();
            $mission->setTitle($titles[$index]);

            $economy = $this->missionEconomyRoller->roll($durationSeconds, $playerLevel);
            $mission->setGoldReward($economy['gold']);
            $mission->setExpReward($economy['exp']);
            $mission->setDurationInSeconds($durationSeconds);
            $mission->setEnergyCost($economy['energy']);
            $mission->setUser($user);

            $user->addMission($mission);
            $this->entityManager->persist($mission);
        }

        $this->entityManager->flush();
    }

    public function resolveOwnedMission(User $user, int $id): Mission
    {
        return $this->ownedTimedActivityResolver->resolveMission($user, $id);
    }

    public function startMission(User $user, int $missionId): void
    {
        $this->userWriteLockExecutor->execute($user, function (User $lockedUser) use ($missionId): void {
            $mission = $this->entityManager->getRepository(Mission::class)->find($missionId);

            if (!$mission) {
                throw new ResourceNotFoundException('missionNotFound');
            }

            if ($mission->getUser()?->getId() !== $lockedUser->getId()) {
                throw new OperationForbiddenException('missionNotYours');
            }

            $lockedUser->spendEnergy($mission->getEnergyCost());
            $this->timedActivityLifecycle->startMission($lockedUser, $mission);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();
        });
    }

    public function cancelMission(User $user): void
    {
        $this->userWriteLockExecutor->execute($user, function (User $lockedUser): void {
            [$currentActivity, $mission] = $this->timedActivityLifecycle->requireActiveMission($lockedUser);

            $lockedUser->restoreEnergy($mission->getEnergyCost());
            $this->timedActivityLifecycle->clear($lockedUser, $currentActivity);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();
        });
    }

    public function completeMission(User $user): array
    {
        return $this->userWriteLockExecutor->execute($user, function (User $lockedUser): array {
            [$currentActivity, $mission] = $this->timedActivityLifecycle->requireActiveMission($lockedUser);

            $this->timedActivityLifecycle->assertElapsed(
                $currentActivity->getStartTime(),
                (int) $mission->getDurationInSeconds(),
                'missionNotComplete',
            );

            return $this->finishActiveMission($lockedUser, $currentActivity, $mission, diamondsSpent: 0);
        });
    }

    public function skipMission(User $user, int $missionId): array
    {
        return $this->userWriteLockExecutor->execute($user, function (User $lockedUser) use ($missionId): array {
            [$currentActivity, $mission] = $this->timedActivityLifecycle->requireActiveMission($lockedUser);

            if ((int) $mission->getId() !== $missionId) {
                throw new BusinessRuleException('missionNotActive');
            }

            $durationSeconds = (int) $mission->getDurationInSeconds();
            $remainingSeconds = MissionConstants::remainingSeconds(
                $currentActivity->getStartTime(),
                $durationSeconds,
            );
            if ($remainingSeconds <= 0) {
                throw new BusinessRuleException('missionAlreadyComplete');
            }

            $cost = MissionConstants::diamondCostToSkip($remainingSeconds);
            $lockedUser->spendDiamonds($cost);

            $completedStart = (new \DateTime())->modify(sprintf('-%d seconds', $durationSeconds));
            $currentActivity->setStartTime($completedStart);
            $this->entityManager->persist($currentActivity);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();

            return [
                'diamondsSpent' => $cost,
                'diamonds' => (int) ($lockedUser->getDiamonds() ?? 0),
                'startTime' => $completedStart->format(\DateTimeInterface::ATOM),
                'readyToClaim' => true,
            ];
        });
    }

    /**
     * @return array{earnedGold: int, earnedExp: int, bonusPercent: int, levelData: array<string, mixed>|null, diamondsSpent: int}
     */
    private function finishActiveMission(
        User $lockedUser,
        UserActualActivity $currentActivity,
        Mission $mission,
        int $diamondsSpent,
    ): array {
        $ship = $this->shipMembershipService->getShipForUser($lockedUser);
        $rewards = $this->missionRewardCalculator->calculate($lockedUser, $mission, $ship);
        $lockedUser->addGold($rewards['gold']);
        $lockedUser->addExperiencePoints($rewards['exp']);

        $levelUpData = $this->levelService->checkAndUpdateLevel($lockedUser);
        $isLevelUp = $levelUpData['levelUp'];

        $this->timedActivityLifecycle->clear($lockedUser, $currentActivity);
        $this->entityManager->persist($lockedUser);
        $this->entityManager->flush();

        $this->regenerateMissionsForUser($lockedUser);

        return [
            'levelData' => $isLevelUp
                ? [
                    'id' => $lockedUser->getLevel()->getId(),
                    'name' => $lockedUser->getLevel()->getName(),
                    'expToNextLevel' => $lockedUser->getLevel()->getExpToNextLevel(),
                ]
                : null,
            'earnedGold' => $rewards['gold'],
            'earnedExp' => $rewards['exp'],
            'bonusPercent' => $rewards['bonusPercent'],
            'diamondsSpent' => $diamondsSpent,
        ];
    }

    public function regenerateMissionsForUser(User $user): void
    {
        foreach ($user->getMissions() as $mission) {
            $this->entityManager->remove($mission);
        }

        $user->getMissions()->clear();

        $this->generateMissionsForUser($user);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    public function formatMissionDtoForUser(User $user, Mission $mission, ?Ship $ship = null): MissionDto
    {
        $ship ??= $this->shipMembershipService->getShipForUser($user);
        $rewards = $this->missionRewardCalculator->calculate($user, $mission, $ship);

        return MissionMapper::fromMission($mission, $rewards);
    }

    public function formatMissionForUser(User $user, Mission $mission, ?Ship $ship = null): array
    {
        return $this->formatMissionDtoForUser($user, $mission, $ship)->toArray();
    }

    /**
     * @return list<MissionDto>
     */
    public function formatMissionDtosForUser(User $user): array
    {
        $ship = $this->shipMembershipService->getShipForUser($user);

        return array_map(
            fn (Mission $mission) => $this->formatMissionDtoForUser($user, $mission, $ship),
            $user->getMissions()->toArray()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function formatMissionsForUser(User $user): array
    {
        return array_map(
            static fn (MissionDto $dto) => $dto->toArray(),
            $this->formatMissionDtosForUser($user)
        );
    }

    /**
     * @param array{earnedGold: int, earnedExp: int, bonusPercent: int, levelData: array<string, mixed>|null, diamondsSpent?: int} $result
     *
     * @return array<string, mixed>
     */
    public function assembleCompletePayload(User $user, array $result): array
    {
        return MissionMapper::completeResponse(
            $this->formatMissionDtosForUser($user),
            $result,
        )->toArray();
    }

    /**
     * @return list<string>
     */
    public static function titlePool(): array
    {
        return MissionTitlePool::all();
    }
}
