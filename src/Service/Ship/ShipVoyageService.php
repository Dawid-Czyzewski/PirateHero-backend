<?php

declare(strict_types=1);

namespace App\Service\Ship;

use App\Domain\Constants\ShipVoyageConstants;
use App\Entity\Ship;
use App\Entity\ShipVoyage;
use App\Entity\User;
use App\Entity\UserActualActivity;
use App\Enum\ShipVoyageStatus;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Repository\ShipMemberRepository;
use App\Repository\ShipVoyageRepository;
use App\Service\Economy\ShipPercentRewardMath;
use App\Service\Progression\TimedActivity\TimedActivityLifecycle;
use App\Service\User\LevelService;
use Doctrine\ORM\EntityManagerInterface;

class ShipVoyageService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ShipVoyageRepository $voyageRepository,
        private readonly ShipMemberRepository $shipMemberRepository,
        private readonly ShipMembershipService $shipMembershipService,
        private readonly TimedActivityLifecycle $timedActivityLifecycle,
        private readonly LevelService $levelService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(User $user): array
    {
        $ship = $this->requireShip($user);
        $active = $this->voyageRepository->findActiveForShip($ship);
        $isOwner = $this->shipMembershipService->isUserOwner($user, $ship);
        $memberActivity = $user->getCurrentActivity();
        $onVoyage = $memberActivity?->getShipVoyage();

        $offers = [];
        foreach (ShipVoyageConstants::OFFER_DURATIONS_SECONDS as $duration) {
            $crewEstimate = max(1, $this->countFreeMembers($ship));
            $offers[] = [
                'durationSeconds' => $duration,
                'hours' => (int) round(ShipVoyageConstants::hoursFromDuration($duration)),
                'goldCost' => ShipVoyageConstants::treasuryCost($duration, $crewEstimate),
                'estimatedGoldPool' => ShipPercentRewardMath::apply(
                    ShipVoyageConstants::baseGold($duration, $crewEstimate),
                    $ship->getMissionsUpgrade(),
                ),
                'estimatedExpPool' => ShipPercentRewardMath::apply(
                    ShipVoyageConstants::baseExp($duration, $crewEstimate),
                    $ship->getMissionsUpgrade(),
                ),
                'estimatedShipFame' => ShipVoyageConstants::shipFame($duration, $ship->getHullUpgrade()),
            ];
        }

        $activePayload = null;
        if ($active instanceof ShipVoyage) {
            $startedAt = $active->getStartedAt();
            $endsAt = $startedAt !== null
                ? (clone \DateTime::createFromInterface($startedAt))->modify('+'.$active->getDurationSeconds().' seconds')
                : null;
            $remaining = 0;
            if ($endsAt !== null) {
                $remaining = max(0, $endsAt->getTimestamp() - (new \DateTime())->getTimestamp());
            }
            $activePayload = [
                'id' => $active->getId(),
                'durationSeconds' => $active->getDurationSeconds(),
                'goldCost' => $active->getGoldCost(),
                'enrolledCount' => $active->getEnrolledCount(),
                'startedAt' => $startedAt?->format(\DateTimeInterface::ATOM),
                'endsAt' => $endsAt?->format(\DateTimeInterface::ATOM),
                'remainingSeconds' => $remaining,
                'readyToComplete' => $remaining <= 0,
                'viewerEnrolled' => $onVoyage !== null && $onVoyage->getId() === $active->getId(),
            ];
        }

        return [
            'shipId' => $ship->getId(),
            'isOwner' => $isOwner,
            'treasuryGold' => $ship->getGold(),
            'missionsUpgrade' => $ship->getMissionsUpgrade(),
            'offers' => $offers,
            'activeVoyage' => $activePayload,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function start(User $user, int $durationSeconds): array
    {
        if (!ShipVoyageConstants::isValidDuration($durationSeconds)) {
            throw new BusinessRuleException('shipVoyageInvalidDuration');
        }

        $ship = $this->requireShip($user);
        if (!$this->shipMembershipService->isUserOwner($user, $ship)) {
            throw new OperationForbiddenException('shipOwnerRequired');
        }
        if ($this->voyageRepository->findActiveForShip($ship) instanceof ShipVoyage) {
            throw new BusinessRuleException('shipVoyageAlreadyActive');
        }

        $freeMembers = $this->collectFreeMembers($ship);
        if ($freeMembers === []) {
            throw new BusinessRuleException('shipVoyageNoFreeCrew');
        }

        $enrolledCount = \count($freeMembers);
        $goldCost = ShipVoyageConstants::treasuryCost($durationSeconds, $enrolledCount);
        if ($ship->getGold() < $goldCost) {
            throw new BusinessRuleException('notEnoughShipGold');
        }

        $baseGold = ShipVoyageConstants::baseGold($durationSeconds, $enrolledCount);
        $baseExp = ShipVoyageConstants::baseExp($durationSeconds, $enrolledCount);

        $ship->setGold($ship->getGold() - $goldCost);

        $voyage = new ShipVoyage();
        $voyage->setShip($ship);
        $voyage->setStartedBy($user);
        $voyage->setDurationSeconds($durationSeconds);
        $voyage->setGoldCost($goldCost);
        $voyage->setBaseGold($baseGold);
        $voyage->setBaseExp($baseExp);
        $voyage->setEnrolledCount($enrolledCount);
        $voyage->setStatus(ShipVoyageStatus::Active);
        $voyage->setStartedAt(new \DateTime());

        $this->entityManager->persist($voyage);
        $this->entityManager->persist($ship);
        $this->entityManager->flush();

        foreach ($freeMembers as $memberUser) {
            $this->timedActivityLifecycle->startVoyage($memberUser, $voyage);
            $this->entityManager->persist($memberUser);
        }
        $this->entityManager->flush();

        return $this->getStatus($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function complete(User $user): array
    {
        $ship = $this->requireShip($user);
        $voyage = $this->voyageRepository->findActiveForShip($ship);
        if (!$voyage instanceof ShipVoyage) {
            throw new BusinessRuleException('shipVoyageNotActive');
        }

        $startedAt = $voyage->getStartedAt();
        if ($startedAt === null) {
            throw new BusinessRuleException('shipVoyageNotActive');
        }
        $this->timedActivityLifecycle->assertElapsed($startedAt, $voyage->getDurationSeconds(), 'shipVoyageNotComplete');

        $enrolled = $this->findEnrolledUsers($voyage);
        $enrolledCount = max(1, \count($enrolled));
        $goldPool = ShipPercentRewardMath::apply($voyage->getBaseGold(), $ship->getMissionsUpgrade());
        $expPool = ShipPercentRewardMath::apply($voyage->getBaseExp(), $ship->getMissionsUpgrade());
        $treasuryCut = (int) floor($goldPool * ShipVoyageConstants::TREASURY_SHARE);
        $personalGold = (int) floor(($goldPool - $treasuryCut) / $enrolledCount);
        $personalExp = (int) floor($expPool / $enrolledCount);
        $shipFame = ShipVoyageConstants::shipFame($voyage->getDurationSeconds(), $ship->getHullUpgrade());

        $ship->addGold($treasuryCut);
        $ship->addFamePoints($shipFame);

        $payouts = [];
        foreach ($enrolled as $sailor) {
            $sailor->addGold($personalGold);
            $sailor->addExperiencePoints($personalExp);
            $levelUp = $this->levelService->checkAndUpdateLevel($sailor);
            $activity = $sailor->getCurrentActivity();
            if ($activity instanceof UserActualActivity && $activity->getShipVoyage()?->getId() === $voyage->getId()) {
                $this->timedActivityLifecycle->clear($sailor, $activity);
            }
            $this->entityManager->persist($sailor);
            $payouts[] = [
                'userId' => $sailor->getId(),
                'username' => $sailor->getUsername(),
                'gold' => $personalGold,
                'exp' => $personalExp,
                'levelUp' => (bool) ($levelUp['levelUp'] ?? false),
            ];
        }

        $voyage->setStatus(ShipVoyageStatus::Completed);
        $voyage->setCompletedAt(new \DateTime());
        $this->entityManager->persist($voyage);
        $this->entityManager->persist($ship);
        $this->entityManager->flush();

        return [
            'rewards' => [
                'personalGold' => $personalGold,
                'personalExp' => $personalExp,
                'treasuryGold' => $treasuryCut,
                'shipFame' => $shipFame,
                'goldPool' => $goldPool,
            ],
            'payouts' => $payouts,
            'status' => $this->getStatus($user),
            'updatedShip' => [
                'gold' => $ship->getGold(),
                'famePoints' => $ship->getFamePoints(),
            ],
            'updatedUser' => [
                'gold' => (int) $user->getGold(),
                'experiencePoints' => (int) $user->getExperiencePoints(),
                'diamonds' => (int) ($user->getDiamonds() ?? 0),
                'freeSkillPointsAvailable' => (int) ($user->getFreeSkillPointsAvailable() ?? 0),
                'level' => [
                    'name' => (string) ($user->getLevel()?->getName() ?? '1'),
                    'expToNextLevel' => (int) ($user->getLevel()?->getExpToNextLevel() ?? 100),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function cancel(User $user): array
    {
        $ship = $this->requireShip($user);
        if (!$this->shipMembershipService->isUserOwner($user, $ship)) {
            throw new OperationForbiddenException('shipOwnerRequired');
        }

        $voyage = $this->voyageRepository->findActiveForShip($ship);
        if (!$voyage instanceof ShipVoyage) {
            throw new BusinessRuleException('shipVoyageNotActive');
        }

        $startedAt = $voyage->getStartedAt();
        if ($startedAt !== null) {
            $endsAt = (clone \DateTime::createFromInterface($startedAt))->modify('+'.$voyage->getDurationSeconds().' seconds');
            if (new \DateTime() >= $endsAt) {
                throw new BusinessRuleException('shipVoyageAlreadyComplete');
            }
        }

        $ship->setGold($ship->getGold() + $voyage->getGoldCost());

        foreach ($this->findEnrolledUsers($voyage) as $sailor) {
            $activity = $sailor->getCurrentActivity();
            if ($activity instanceof UserActualActivity && $activity->getShipVoyage()?->getId() === $voyage->getId()) {
                $this->timedActivityLifecycle->clear($sailor, $activity);
            }
            $this->entityManager->persist($sailor);
        }

        $voyage->setStatus(ShipVoyageStatus::Cancelled);
        $voyage->setCompletedAt(new \DateTime());
        $this->entityManager->persist($voyage);
        $this->entityManager->persist($ship);
        $this->entityManager->flush();

        return [
            'refundedGold' => $voyage->getGoldCost(),
            'status' => $this->getStatus($user),
            'updatedShip' => [
                'gold' => $ship->getGold(),
                'famePoints' => $ship->getFamePoints(),
            ],
        ];
    }

    public function clearVoyageActivityForUser(User $user): void
    {
        $activity = $user->getCurrentActivity();
        if ($activity instanceof UserActualActivity && $activity->getShipVoyage() !== null) {
            $this->timedActivityLifecycle->clear($user, $activity);
            $this->entityManager->persist($user);
        }
    }

    private function requireShip(User $user): Ship
    {
        $ship = $this->shipMembershipService->getShipForUser($user);
        if ($ship === null) {
            throw new OperationForbiddenException('shipMembershipRequired');
        }

        return $ship;
    }

    private function countFreeMembers(Ship $ship): int
    {
        return \count($this->collectFreeMembers($ship));
    }

    /**
     * @return list<User>
     */
    private function collectFreeMembers(Ship $ship): array
    {
        $free = [];
        foreach ($this->shipMemberRepository->findBy(['ship' => $ship]) as $member) {
            $memberUser = $member->getUser();
            if ($memberUser === null) {
                continue;
            }
            if ($memberUser->getCurrentActivity() === null) {
                $free[] = $memberUser;
            }
        }

        return $free;
    }

    /**
     * @return list<User>
     */
    private function findEnrolledUsers(ShipVoyage $voyage): array
    {
        $users = [];
        $ship = $voyage->getShip();
        if ($ship === null) {
            return [];
        }
        foreach ($this->shipMemberRepository->findBy(['ship' => $ship]) as $member) {
            $memberUser = $member->getUser();
            if ($memberUser === null) {
                continue;
            }
            $activity = $memberUser->getCurrentActivity();
            if ($activity?->getShipVoyage()?->getId() === $voyage->getId()) {
                $users[] = $memberUser;
            }
        }

        return $users;
    }
}
