<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Domain\Constants\TreasureMapConstants;
use App\Entity\User;
use App\Entity\UserTreasureMap;
use App\Enum\TreasureMapStepType;
use App\Exception\BusinessRuleException;
use App\Repository\UserTreasureMapRepository;
use App\Service\Economy\WearableRewardFactory;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class TreasureMapProgressService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserTreasureMapRepository $repository,
        private readonly WearableRewardFactory $wearableRewardFactory,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(User $user): array
    {
        $map = $this->ensureMap($user);

        return $this->buildStatusPayload($user, $map);
    }

    /**
     * @return array<string, mixed>
     */
    public function claimChest(User $user): array
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $locked = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$locked instanceof User) {
                throw new BusinessRuleException('userNotFound');
            }

            $map = $this->ensureMap($locked);
            if (!$map->isCompleted()) {
                throw new BusinessRuleException('treasureMapNotComplete');
            }
            if ($map->isChestClaimed()) {
                throw new BusinessRuleException('treasureMapAlreadyClaimed');
            }

            $level = $this->resolveLevel($locked);
            $reward = TreasureMapConstants::chestReward($level);
            $locked->addGold($reward['gold']);
            $locked->addDiamonds($reward['diamonds']);

            $item = $this->wearableRewardFactory->createRandomForUser($locked);
            $this->wearableRewardFactory->placeInStorage($locked, $item);

            $map->setChestClaimed(true);
            $this->entityManager->persist($locked);
            $this->entityManager->persist($map);
            $this->entityManager->flush();
            $connection->commit();

            return [
                'rewards' => $reward,
                'item' => [
                    'id' => $item->getId(),
                    'nameKey' => method_exists($item, 'getNameKey') ? $item->getNameKey() : null,
                    'rarity' => $item->getRarity()?->value,
                    'type' => $item->getType()?->value,
                ],
                'updatedUser' => $this->userSnapshot($locked),
                'status' => $this->buildStatusPayload($locked, $map),
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function record(User $user, TreasureMapStepType $type, int $amount = 1): void
    {
        if ($amount <= 0) {
            return;
        }

        $map = $this->ensureMap($user);
        if ($map->isCompleted() || $map->isChestClaimed()) {
            return;
        }

        $steps = TreasureMapConstants::steps();
        $stepIndex = $map->getCurrentStep() - 1;
        if ($stepIndex < 0 || $stepIndex >= \count($steps)) {
            return;
        }

        $current = $steps[$stepIndex];
        if ($current['type'] !== $type->value) {
            return;
        }

        $progress = $map->getStepProgress();
        while (\count($progress) < \count($steps)) {
            $progress[] = 0;
        }
        $progress[$stepIndex] = min($current['target'], $progress[$stepIndex] + $amount);
        $map->setStepProgress($progress);

        if ($progress[$stepIndex] >= $current['target']) {
            if ($stepIndex + 1 >= \count($steps)) {
                $map->setCompleted(true);
            } else {
                $map->setCurrentStep($stepIndex + 2);
            }
        }

        $this->entityManager->persist($map);
        $this->entityManager->flush();
    }

    public function recordMissions(User $user, int $amount = 1): void
    {
        $this->record($user, TreasureMapStepType::Missions, $amount);
    }

    public function recordArenaWins(User $user, int $amount = 1): void
    {
        $this->record($user, TreasureMapStepType::ArenaWins, $amount);
    }

    public function recordDungeonStageWin(User $user, int $amount = 1): void
    {
        $this->record($user, TreasureMapStepType::DungeonStageWin, $amount);
    }

    public function recordGoldSpent(User $user, int $amount): void
    {
        $this->record($user, TreasureMapStepType::GoldSpent, $amount);
    }

    public function recordShipVoyageComplete(User $user, int $amount = 1): void
    {
        $this->record($user, TreasureMapStepType::ShipVoyageComplete, $amount);
    }

    private function ensureMap(User $user): UserTreasureMap
    {
        $weekStart = $this->weekStart();
        $existing = $this->repository->findOneForUserWeek($user, $weekStart);
        if ($existing instanceof UserTreasureMap) {
            return $existing;
        }

        $map = new UserTreasureMap();
        $map->setUser($user);
        $map->setWeekStart($weekStart);
        $map->setCurrentStep(1);
        $map->setStepProgress(array_fill(0, TreasureMapConstants::stepCount(), 0));
        $map->setCompleted(false);
        $map->setChestClaimed(false);
        $this->entityManager->persist($map);
        $this->entityManager->flush();

        return $map;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStatusPayload(User $user, UserTreasureMap $map): array
    {
        $defs = TreasureMapConstants::steps();
        $progress = $map->getStepProgress();
        $steps = [];
        foreach ($defs as $i => $def) {
            $slot = $i + 1;
            $prog = (int) ($progress[$i] ?? 0);
            $target = $def['target'];
            $done = $prog >= $target;
            $unlocked = $slot <= $map->getCurrentStep() || $done || $map->isCompleted();
            $steps[] = [
                'slot' => $slot,
                'type' => $def['type'],
                'target' => $target,
                'progress' => min($prog, $target),
                'complete' => $done,
                'unlocked' => $unlocked,
                'current' => !$map->isCompleted() && $slot === $map->getCurrentStep(),
            ];
        }

        $level = $this->resolveLevel($user);
        $canClaim = $map->isCompleted() && !$map->isChestClaimed();

        return [
            'weekStart' => $map->getWeekStart()->format('Y-m-d'),
            'weekEnd' => $map->getWeekStart()->modify('+6 days')->format('Y-m-d'),
            'currentStep' => $map->getCurrentStep(),
            'completed' => $map->isCompleted(),
            'chestClaimed' => $map->isChestClaimed(),
            'canClaim' => $canClaim,
            'rewards' => TreasureMapConstants::chestReward($level),
            'steps' => $steps,
            'unclaimedCount' => $canClaim ? 1 : 0,
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
