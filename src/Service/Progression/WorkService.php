<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Dto\Api\Work\WorkDto;
use App\Entity\Ship;
use App\Entity\User;
use App\Entity\Work;
use App\Exception\OperationForbiddenException;
use App\Mapper\Api\WorkMapper;
use App\Service\Progression\TimedActivity\OwnedTimedActivityResolver;
use App\Service\Progression\TimedActivity\TimedActivityLifecycle;
use App\Service\Ship\ShipMembershipService;
use Doctrine\ORM\EntityManagerInterface;

readonly class WorkService
{
    public const WORK_OFFER_COUNT = 5;

    /**
     * @var list<array{title: string, hoursCount: int, baseGold: int}>
     */
    private const WORK_DEFINITIONS = [
        [
            'title' => 'work.kitchen_helper',
            'hoursCount' => 2,
            'baseGold' => 12,
        ],
        [
            'title' => 'work.warehouse_loader',
            'hoursCount' => 4,
            'baseGold' => 11,
        ],
        [
            'title' => 'work.car_wash_attendant',
            'hoursCount' => 3,
            'baseGold' => 13,
        ],
        [
            'title' => 'work.port_dockhand',
            'hoursCount' => 5,
            'baseGold' => 10,
        ],
        [
            'title' => 'work.tavern_server',
            'hoursCount' => 6,
            'baseGold' => 9,
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ShipMembershipService $shipMembershipService,
        private readonly WorkRewardCalculator $workRewardCalculator,
        private readonly UserWriteLockExecutor $userWriteLockExecutor,
        private readonly TimedActivityLifecycle $timedActivityLifecycle,
        private readonly OwnedTimedActivityResolver $ownedTimedActivityResolver,
    ) {
    }

    public function generateWorksForUser(User $user): void
    {
        foreach (self::WORK_DEFINITIONS as $def) {
            $work = new Work();
            $work->setTitle($def['title']);
            $work->setBaseGold($def['baseGold']);
            $work->setHoursCount($def['hoursCount']);
            $work->setUser($user);

            $user->addWork($work);
            $this->entityManager->persist($work);
        }

        $this->entityManager->flush();
    }

    public function startWork(User $user, Work $work): void
    {
        if ($work->getUser()?->getId() !== $user->getId()) {
            throw new OperationForbiddenException('workNotYours');
        }

        $this->userWriteLockExecutor->execute($user, function (User $lockedUser) use ($work): void {
            $this->timedActivityLifecycle->startWork($lockedUser, $work);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();
        });
    }

    public function cancelWork(User $user): void
    {
        $this->userWriteLockExecutor->execute($user, function (User $lockedUser): void {
            [$activity] = $this->timedActivityLifecycle->requireActiveWork($lockedUser);
            $this->timedActivityLifecycle->clear($lockedUser, $activity);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();
        });
    }

    /**
     * @return array{earnedGold: int, bonusPercent: int}
     */
    public function completeWork(User $user): array
    {
        return $this->userWriteLockExecutor->execute($user, function (User $lockedUser): array {
            [$activity, $work] = $this->timedActivityLifecycle->requireActiveWork($lockedUser);

            $this->timedActivityLifecycle->assertElapsedHours(
                $activity->getStartTime(),
                (int) $work->getHoursCount(),
                'workNotComplete',
            );

            $rewards = $this->workRewardCalculator->calculate(
                $lockedUser,
                $work,
                $this->shipMembershipService->getShipForUser($lockedUser),
            );
            $goldEarned = $rewards['totalGold'];
            $lockedUser->addGold($goldEarned);

            $this->timedActivityLifecycle->clear($lockedUser, $activity);

            foreach ($lockedUser->getWorks() as $userWork) {
                $this->entityManager->remove($userWork);
            }

            $lockedUser->getWorks()->clear();

            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();

            $this->generateWorksForUser($lockedUser);

            return [
                'earnedGold' => $goldEarned,
                'bonusPercent' => $rewards['bonusPercent'],
            ];
        });
    }

    public function resolveOwnedWork(User $user, int $id): Work
    {
        return $this->ownedTimedActivityResolver->resolveWork($user, $id);
    }

    public function formatWorkDtoForUser(User $user, Work $work, ?Ship $ship = null): WorkDto
    {
        $ship ??= $this->shipMembershipService->getShipForUser($user);
        $rewards = $this->workRewardCalculator->calculate($user, $work, $ship);
        $levelValue = UserLevelResolver::of($user);

        return WorkMapper::fromWork($work, $rewards, $levelValue);
    }

    /**
     * @return array<string, mixed>
     */
    public function formatWorkForUser(User $user, Work $work, ?Ship $ship = null): array
    {
        return $this->formatWorkDtoForUser($user, $work, $ship)->toArray();
    }

    /**
     * @return list<WorkDto>
     */
    public function formatWorkDtosForUser(User $user): array
    {
        $ship = $this->shipMembershipService->getShipForUser($user);

        return array_map(
            fn (Work $work) => $this->formatWorkDtoForUser($user, $work, $ship),
            $user->getWorks()->toArray()
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function formatWorksForUser(User $user): array
    {
        return array_map(
            static fn (WorkDto $dto) => $dto->toArray(),
            $this->formatWorkDtosForUser($user)
        );
    }
}
