<?php

declare(strict_types=1);

namespace App\Service\Economy;

use App\Config\DailyRewardCatalog;
use App\Entity\User;
use App\Entity\UserDailyReward;
use App\Entity\WearableItem;
use App\Enum\WearableItemRarity;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Repository\UserDailyRewardRepository;
use App\Service\User\LevelService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class DailyRewardService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserDailyRewardRepository $dailyRewardRepository,
        private LevelService $levelService,
        private WearableRewardFactory $wearableRewardFactory,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(User $user): array
    {
        $progress = $this->getOrCreateProgress($user);
        $this->applyMissedDayReset($progress);

        $nextDay = $progress->getNextDay();
        $claimedToday = $this->hasClaimedToday($progress);
        $canClaim = !$claimedToday;
        $highestClaimedDay = $this->resolveHighestClaimedDay($nextDay, $claimedToday);

        return [
            'canClaim' => $canClaim,
            'claimedToday' => $claimedToday,
            'currentDay' => $nextDay,
            'highestClaimedDay' => $highestClaimedDay,
            'totalDays' => DailyRewardCatalog::TOTAL_DAYS,
            'schedule' => DailyRewardCatalog::getSchedule(),
            'todayReward' => [
                'day' => $nextDay,
                'rewards' => DailyRewardCatalog::rewardsForDay($nextDay),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function claim(User $user): array
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $locked = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$locked instanceof User) {
                throw new ResourceNotFoundException('userNotFound');
            }

            $progress = $this->getOrCreateProgress($locked);
            $this->applyMissedDayReset($progress);

            if ($this->hasClaimedToday($progress)) {
                throw new BusinessRuleException('dailyRewardAlreadyClaimed');
            }

            $response = $this->grantDailyReward($locked, $progress);

            $this->entityManager->flush();
            $connection->commit();

            $this->entityManager->refresh($locked);
            $this->entityManager->refresh($progress);

            return $this->enrichClaimResponse($response, $locked, $progress);
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function grantDailyReward(User $user, UserDailyReward $progress): array
    {
        $day = $progress->getNextDay();
        $rewardDefinitions = DailyRewardCatalog::rewardsForDay($day);
        $grantedRewards = [];
        $levelUpData = null;
        $rewardItem = null;

        foreach ($rewardDefinitions as $definition) {
            $type = $definition['type'];
            $amount = $definition['amount'] ?? 0;

            switch ($type) {
                case 'gold':
                    $user->addGold($amount);
                    $grantedRewards[] = ['type' => 'gold', 'amount' => $amount];
                    break;
                case 'diamonds':
                    $user->addDiamonds($amount);
                    $grantedRewards[] = ['type' => 'diamonds', 'amount' => $amount];
                    break;
                case 'experience':
                    $user->addExperiencePoints($amount);
                    $levelUpData = $this->levelService->checkAndUpdateLevel($user);
                    $grantedRewards[] = ['type' => 'experience', 'amount' => $amount];
                    break;
                case 'item':
                    $rewardItem = $this->generateRandomItemForUser($user);
                    $this->wearableRewardFactory->placeInStorage($user, $rewardItem);
                    $grantedRewards[] = ['type' => 'item'];
                    break;
                default:
                    break;
            }
        }

        if ($day >= DailyRewardCatalog::TOTAL_DAYS) {
            $progress->setNextDay(1);
        } else {
            $progress->setNextDay($day + 1);
        }

        $progress->setLastClaimDate(new \DateTime('today'));
        $this->entityManager->persist($user);
        $this->entityManager->persist($progress);

        $response = [
            'message' => 'dailyRewardClaimed',
            'claimedDay' => $day,
            'grantedRewards' => $grantedRewards,
            'updatedUser' => [
                'gold' => $user->getGold(),
                'diamonds' => $user->getDiamonds(),
                'experiencePoints' => $user->getExperiencePoints(),
                'freeSkillPointsAvailable' => $user->getFreeSkillPointsAvailable(),
            ],
            'status' => $this->getStatus($user),
            '_levelUpData' => $levelUpData,
            '_rewardItem' => $rewardItem,
        ];

        return $response;
    }

    /**
     * @param array<string, mixed> $response
     *
     * @return array<string, mixed>
     */
    private function enrichClaimResponse(array $response, User $user, UserDailyReward $progress): array
    {
        $levelUpData = $response['_levelUpData'] ?? null;
        $rewardItem = $response['_rewardItem'] ?? null;
        unset($response['_levelUpData'], $response['_rewardItem']);

        if ($levelUpData && ($levelUpData['levelUp'] ?? false)) {
            $response['updatedUser']['level'] = [
                'id' => $user->getLevel()->getId(),
                'name' => $user->getLevel()->getName(),
                'expToNextLevel' => $user->getLevel()->getExpToNextLevel(),
            ];
            $response['newLevel'] = $response['updatedUser']['level'];
        } elseif ($user->getLevel()) {
            $response['updatedUser']['level'] = [
                'id' => $user->getLevel()->getId(),
                'name' => $user->getLevel()->getName(),
                'expToNextLevel' => $user->getLevel()->getExpToNextLevel(),
            ];
        }

        if ($rewardItem instanceof WearableItem) {
            $this->entityManager->refresh($rewardItem);
            $storage = $user->getStorage();
            if ($storage) {
                $slots = [];
                foreach ($storage->getSlots() as $slot) {
                    $slotItem = $slot->getItem();
                    $slots[] = [
                        'id' => $slot->getId(),
                        'slotNumber' => $slot->getSlotNumber(),
                        'item' => $slotItem ? $this->wearableRewardFactory->toClientPayload($slotItem) : null,
                    ];
                }
                $response['updatedUser']['storage'] = [
                    'id' => $storage->getId(),
                    'slots' => $slots,
                ];
            }

            try {
                $response['rewardItem'] = $this->wearableRewardFactory->toClientPayload($rewardItem);
            } catch (\Exception $e) {
                $this->logger->warning('dailyReward.itemSerializeFailed', [
                    'exception' => $e,
                    'itemId' => $rewardItem->getId(),
                ]);
                $response['rewardItem'] = [
                    'id' => $rewardItem->getId(),
                    'name' => 'Item',
                    'type' => null,
                    'rarity' => 'RARE',
                    'price' => 0,
                    'statistics' => null,
                ];
            }
        }

        return $response;
    }

    private function getOrCreateProgress(User $user): UserDailyReward
    {
        $existing = $this->dailyRewardRepository->findOneForUser($user);
        if ($existing instanceof UserDailyReward) {
            return $existing;
        }

        $progress = new UserDailyReward();
        $progress->setUser($user);
        $progress->setNextDay(1);
        $this->entityManager->persist($progress);
        $this->entityManager->flush();

        return $progress;
    }

    private function applyMissedDayReset(UserDailyReward $progress): void
    {
        $lastClaim = $progress->getLastClaimDate();
        if ($lastClaim === null) {
            return;
        }

        $today = new \DateTime('today');
        $lastDay = \DateTimeImmutable::createFromInterface($lastClaim)->setTime(0, 0, 0);

        if ($lastDay >= $today) {
            return;
        }

        $yesterday = (clone $today)->modify('-1 day');
        if ($lastDay < $yesterday) {
            $progress->setNextDay(1);
            $this->entityManager->persist($progress);
            $this->entityManager->flush();
        }
    }

    private function hasClaimedToday(UserDailyReward $progress): bool
    {
        $lastClaim = $progress->getLastClaimDate();
        if ($lastClaim === null) {
            return false;
        }

        $today = new \DateTime('today');
        $lastDay = \DateTimeImmutable::createFromInterface($lastClaim)->setTime(0, 0, 0);

        return $lastDay >= $today;
    }

    private function resolveHighestClaimedDay(int $nextDay, bool $claimedToday): int
    {
        if ($claimedToday) {
            return $nextDay === 1 ? DailyRewardCatalog::TOTAL_DAYS : $nextDay - 1;
        }

        return max(0, $nextDay - 1);
    }

    private function generateRandomItemForUser(User $user): WearableItem
    {
        return $this->wearableRewardFactory->createForUser($user, WearableItemRarity::RARE);
    }
}
