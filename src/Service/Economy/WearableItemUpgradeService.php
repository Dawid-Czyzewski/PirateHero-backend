<?php

declare(strict_types=1);

namespace App\Service\Economy;

use App\Domain\Constants\WearableUpgradeConstants;
use App\Entity\User;
use App\Entity\UserEquipmentSlot;
use App\Entity\WearableItem;
use App\Enum\WearableSpecialization;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Service\Progression\DailyChallengeService;
use App\Service\Progression\WeeklyContractService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class WearableItemUpgradeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DailyChallengeService $dailyChallengeService,
        #[Autowire(lazy: true)]
        private readonly WeeklyContractService $weeklyContractService,
    ) {
    }

    /**
     * @return array{
     *   itemId: int,
     *   upgradeLevel: int,
     *   maxUpgradeLevel: int,
     *   goldSpent: int,
     *   price: int,
     *   statistics: array<string, int>,
     *   gold: int
     * }
     */
    public function upgrade(User $user, int $itemId): array
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $lockedUser = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$lockedUser instanceof User) {
                throw new ResourceNotFoundException('userNotFound');
            }

            $item = $this->entityManager->find(WearableItem::class, $itemId, LockMode::PESSIMISTIC_WRITE);
            if (!$item instanceof WearableItem) {
                throw new ResourceNotFoundException('wearableItemNotFound');
            }

            if (!$this->userOwnsItem($lockedUser, $item)) {
                throw new BusinessRuleException('itemNotOwned');
            }

            $max = WearableUpgradeConstants::maxLevelFor($item->getRarity());
            $current = $item->getUpgradeLevel();
            if ($current >= $max) {
                throw new BusinessRuleException('itemUpgradeMaxReached');
            }

            $cost = WearableUpgradeConstants::goldCost($current, $item->getRarity());
            $lockedUser->spendGold($cost);
            $this->dailyChallengeService->recordGoldSpent($lockedUser, $cost);
            $this->weeklyContractService->recordGoldSpent($lockedUser, $cost);

            $item->setUpgradeLevel($current + 1);
            $stats = $item->getStatistics();
            if ($stats !== null) {
                $stats->bumpPositiveStatsByOne();
                $this->entityManager->persist($stats);
            }

            $item->setPrice((int) ($item->getPrice() ?? 0) + $cost);
            $this->entityManager->persist($item);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();
            $connection->commit();

            $user->setGold($lockedUser->getGold());

            return [
                'itemId' => (int) $item->getId(),
                'upgradeLevel' => $item->getUpgradeLevel(),
                'maxUpgradeLevel' => $max,
                'goldSpent' => $cost,
                'price' => (int) $item->getPrice(),
                'statistics' => $stats?->toClientArray() ?? [],
                'gold' => (int) $lockedUser->getGold(),
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * @return array{
     *   itemId: int,
     *   specialization: string,
     *   goldSpent: int,
     *   price: int,
     *   statistics: array<string, int>,
     *   gold: int
     * }
     */
    public function specialize(User $user, int $itemId, string $specializationRaw): array
    {
        $specialization = WearableSpecialization::tryFromClient($specializationRaw);
        if ($specialization === null) {
            throw new BusinessRuleException('invalidSpecialization');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $lockedUser = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$lockedUser instanceof User) {
                throw new ResourceNotFoundException('userNotFound');
            }

            $item = $this->entityManager->find(WearableItem::class, $itemId, LockMode::PESSIMISTIC_WRITE);
            if (!$item instanceof WearableItem) {
                throw new ResourceNotFoundException('wearableItemNotFound');
            }

            if (!$this->userOwnsItem($lockedUser, $item)) {
                throw new BusinessRuleException('itemNotOwned');
            }

            $max = WearableUpgradeConstants::maxLevelFor($item->getRarity());
            if ($item->getUpgradeLevel() < $max) {
                throw new BusinessRuleException('itemSpecializationRequiresMaxUpgrade');
            }
            if ($item->getSpecialization() !== null) {
                throw new BusinessRuleException('itemSpecializationAlreadySet');
            }

            $cost = WearableUpgradeConstants::specializationGoldCost($item->getRarity());
            $lockedUser->spendGold($cost);
            $this->dailyChallengeService->recordGoldSpent($lockedUser, $cost);
            $this->weeklyContractService->recordGoldSpent($lockedUser, $cost);

            $stats = $item->getStatistics();
            if ($specialization === WearableSpecialization::Health && $stats !== null) {
                $stats->setHealthPoints($stats->getHealthPoints() + WearableUpgradeConstants::HEALTH_BONUS);
                $this->entityManager->persist($stats);
            } elseif ($specialization === WearableSpecialization::Crit && $stats !== null) {
                $stats->setCriticalChancePoints(
                    $stats->getCriticalChancePoints() + WearableUpgradeConstants::CRIT_BONUS
                );
                $this->entityManager->persist($stats);
            }

            $item->setSpecialization($specialization->value);
            $item->setPrice((int) ($item->getPrice() ?? 0) + $cost);
            $this->entityManager->persist($item);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();
            $connection->commit();

            $user->setGold($lockedUser->getGold());

            return [
                'itemId' => (int) $item->getId(),
                'specialization' => $specialization->value,
                'goldSpent' => $cost,
                'price' => (int) $item->getPrice(),
                'statistics' => $stats?->toClientArray() ?? [],
                'gold' => (int) $lockedUser->getGold(),
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * @return array{upgradeLevel: int, maxUpgradeLevel: int, nextCost: int|null, canUpgrade: bool}
     */
    public function preview(WearableItem $item): array
    {
        $max = WearableUpgradeConstants::maxLevelFor($item->getRarity());
        $level = $item->getUpgradeLevel();
        $can = $level < $max;

        return [
            'upgradeLevel' => $level,
            'maxUpgradeLevel' => $max,
            'nextCost' => $can ? WearableUpgradeConstants::goldCost($level, $item->getRarity()) : null,
            'canUpgrade' => $can,
        ];
    }

    public static function equippedMissionGoldPercent(User $user): int
    {
        $equipment = $user->getUserEquipment();
        if ($equipment === null) {
            return 0;
        }

        $percent = 0;
        foreach ($equipment->getUserEquipmentSlots() as $slot) {
            $item = $slot->getWearableItem();
            if ($item !== null && $item->getSpecialization() === WearableSpecialization::MissionGold->value) {
                $percent += WearableUpgradeConstants::MISSION_GOLD_PERCENT;
            }
        }

        return min(WearableUpgradeConstants::MISSION_GOLD_PERCENT_CAP, $percent);
    }

    private function userOwnsItem(User $user, WearableItem $item): bool
    {
        $storage = $user->getStorage();
        if ($storage !== null) {
            foreach ($storage->getSlots() as $slot) {
                if ($slot->getItem()?->getId() === $item->getId()) {
                    return true;
                }
            }
        }

        $equipment = $user->getUserEquipment();
        if ($equipment !== null) {
            foreach ($equipment->getUserEquipmentSlots() as $slot) {
                if ($slot->getWearableItem()?->getId() === $item->getId()) {
                    return true;
                }
            }
        }

        return false;
    }
}
