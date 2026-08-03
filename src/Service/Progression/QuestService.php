<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Entity\User;
use App\Entity\UserQuest;
use App\Entity\WearableItem;
use App\Enum\QuestCategory;
use App\Enum\QuestRewardType;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Repository\UserQuestRepository;
use App\Service\Economy\WearableRewardFactory;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

readonly class QuestService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserQuestRepository $userQuestRepository,
        private readonly QuestProgressService $questProgressService,
        private readonly QuestRewardClaimer $questRewardClaimer,
        private readonly WearableRewardFactory $wearableRewardFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getUserQuests(User $user): array
    {
        $this->questProgressService->initializeUserQuests($user);

        return $this->formatQuestsResponse($user);
    }

    public function formatQuestsResponse(User $user): array
    {
        $userQuests = $this->userQuestRepository->findByUser($user);

        $questsData = [];
        $hasUnclaimedRewards = false;
        $unclaimedCount = 0;

        foreach ($userQuests as $userQuest) {
            $template = $userQuest->getQuestTemplate();
            if (!$template) {
                continue;
            }

            $isCompleted = $userQuest->isCompleted();
            $isRewardClaimed = $userQuest->isRewardClaimed();

            if ($isCompleted && !$isRewardClaimed) {
                $hasUnclaimedRewards = true;
                ++$unclaimedCount;
            }

            $questsData[] = [
                'id' => $userQuest->getId(),
                'templateId' => $template->getId(),
                'code' => $template->getCode(),
                'title' => $template->getTitle(),
                'description' => $template->getDescription(),
                'category' => $template->getCategory()->value,
                'targetValue' => $template->getTargetValue(),
                'currentProgress' => $userQuest->getCurrentProgress(),
                'progressPercentage' => $userQuest->getProgressPercentage(),
                'isCompleted' => $isCompleted,
                'isRewardClaimed' => $isRewardClaimed,
                'completedAt' => $userQuest->getCompletedAt()?->format('Y-m-d H:i:s'),
                'rewardType' => $template->getRewardType()->value,
                'rewardAmount' => $template->getRewardAmount(),
                'secondaryRewardType' => $template->getSecondaryRewardType()?->value,
                'secondaryRewardAmount' => $template->getSecondaryRewardAmount(),
                'rewardItem' => ($template->getRewardType() === QuestRewardType::ITEM) ? [
                    'type' => 'RANDOM_ITEM',
                    'rarity' => $template->getCode() === 'fight_veteran_500' ? 'RARE_OR_EPIC' : 'RARE',
                ] : ($template->getRewardItem() ? [
                    'id' => $template->getRewardItem()->getId(),
                    'name' => $template->getRewardItem()->getName(),
                ] : null),
            ];
        }

        usort($questsData, static function (array $a, array $b): int {
            $aReady = ($a['isCompleted'] ?? false) && !($a['isRewardClaimed'] ?? false);
            $bReady = ($b['isCompleted'] ?? false) && !($b['isRewardClaimed'] ?? false);
            if ($aReady === $bReady) {
                return 0;
            }

            return $aReady ? -1 : 1;
        });

        return [
            'quests' => $questsData,
            'hasUnclaimedRewards' => $hasUnclaimedRewards,
            'unclaimedCount' => $unclaimedCount,
        ];
    }

    /**
     * Merge quest list + unclaimed meta into an existing API payload (shop, fight, mission, …).
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function mergeQuestPayload(array $payload, User $user): array
    {
        $questsResponse = $this->formatQuestsResponse($user);
        $payload['quests'] = $questsResponse['quests'];
        $payload['hasUnclaimedRewards'] = $questsResponse['hasUnclaimedRewards'];
        $payload['unclaimedCount'] = $questsResponse['unclaimedCount'];

        return $payload;
    }

    public function claimReward(User $user, int $questId): array
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        $levelUpData = null;
        $randomItem = null;
        $rewardType = null;
        $rewardAmount = 0;

        try {
            $userQuest = $this->entityManager->find(UserQuest::class, $questId, LockMode::PESSIMISTIC_WRITE);
            if (!$userQuest) {
                throw new ResourceNotFoundException('questNotFound');
            }

            if ($userQuest->getUser()->getId() !== $user->getId()) {
                throw new OperationForbiddenException('questAccessDenied');
            }

            if (!$userQuest->isCompleted()) {
                throw new BusinessRuleException('questNotCompleted');
            }

            if ($userQuest->isRewardClaimed()) {
                throw new BusinessRuleException('questRewardAlreadyClaimed');
            }

            $lockedUser = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$lockedUser instanceof User) {
                throw new ResourceNotFoundException('userNotFound');
            }

            $template = $userQuest->getQuestTemplate();
            $application = $this->questRewardClaimer->applyRewards($lockedUser, $userQuest);
            $rewardType = $application->rewardType;
            $rewardAmount = $application->rewardAmount;
            $levelUpData = $application->levelUpData;
            $randomItem = $application->randomItem;

            $userQuest->claimReward();
            $this->entityManager->flush();
            $connection->commit();

            $this->entityManager->refresh($lockedUser);
            if ($randomItem instanceof WearableItem) {
                $this->entityManager->refresh($randomItem);
            }

            $user = $lockedUser;
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }

        $responseData = [
            'message' => 'questRewardClaimed',
            'rewardType' => $rewardType->value,
            'rewardAmount' => $rewardAmount,
            'updatedUser' => [
                'gold' => $user->getGold(),
                'diamonds' => $user->getDiamonds(),
                'experiencePoints' => $user->getExperiencePoints(),
                'freeSkillPointsAvailable' => $user->getFreeSkillPointsAvailable(),
            ],
        ];

        if ($levelUpData && $levelUpData['levelUp']) {
            $responseData['updatedUser']['level'] = [
                'id' => $user->getLevel()->getId(),
                'name' => $user->getLevel()->getName(),
                'expToNextLevel' => $user->getLevel()->getExpToNextLevel(),
            ];
            $responseData['newLevel'] = $responseData['updatedUser']['level'];
        } elseif ($rewardType === QuestRewardType::EXPERIENCE) {
            if ($user->getLevel()) {
                $responseData['updatedUser']['level'] = [
                    'id' => $user->getLevel()->getId(),
                    'name' => $user->getLevel()->getName(),
                    'expToNextLevel' => $user->getLevel()->getExpToNextLevel(),
                ];
            }
        }

        if ($rewardType === QuestRewardType::ITEM && $randomItem) {
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
                $responseData['updatedUser']['storage'] = [
                    'id' => $storage->getId(),
                    'slots' => $slots,
                ];
            }

            try {
                $responseData['rewardItem'] = $this->wearableRewardFactory->toClientPayload($randomItem);
            } catch (\Exception $e) {
                $this->logger->warning('quest.reward.itemSerializeFailed', [
                    'exception' => $e,
                    'itemId' => $randomItem->getId(),
                ]);
                $responseData['rewardItem'] = [
                    'id' => $randomItem->getId(),
                    'name' => 'Item',
                    'type' => null,
                    'rarity' => 'RARE',
                    'price' => 0,
                    'statistics' => null,
                ];
            }
        }

        $nextQuest = $this->findNextQuestInCategory($user, $template->getCategory());
        if ($nextQuest) {
            $nextTemplate = $nextQuest->getQuestTemplate();
            $responseData['nextQuest'] = [
                'id' => $nextQuest->getId(),
                'templateId' => $nextTemplate->getId(),
                'title' => $nextTemplate->getTitle(),
                'description' => $nextTemplate->getDescription(),
                'category' => $nextTemplate->getCategory()->value,
                'targetValue' => $nextTemplate->getTargetValue(),
                'currentProgress' => $nextQuest->getCurrentProgress(),
                'progressPercentage' => $nextQuest->getProgressPercentage(),
                'isCompleted' => $nextQuest->isCompleted(),
                'isRewardClaimed' => $nextQuest->isRewardClaimed(),
                'completedAt' => $nextQuest->getCompletedAt()?->format('Y-m-d H:i:s'),
                'rewardType' => $nextTemplate->getRewardType()->value,
                'rewardAmount' => $nextTemplate->getRewardAmount(),
                'rewardItem' => ($nextTemplate->getRewardType() === QuestRewardType::ITEM) ? [
                    'type' => 'RANDOM_ITEM',
                    'rarity' => 'RARE',
                ] : ($nextTemplate->getRewardItem() ? [
                    'id' => $nextTemplate->getRewardItem()->getId(),
                    'name' => $nextTemplate->getRewardItem()->getName(),
                ] : null),
            ];
        }

        $userQuests = $this->userQuestRepository->findByUser($user);
        $unclaimedCount = 0;
        foreach ($userQuests as $uq) {
            if ($uq->isCompleted() && !$uq->isRewardClaimed()) {
                ++$unclaimedCount;
            }
        }
        $responseData['unclaimedCount'] = $unclaimedCount;

        return $responseData;
    }

    private function findNextQuestInCategory(User $user, QuestCategory $category): ?UserQuest
    {
        $this->questProgressService->initializeUserQuests($user);

        $allUserQuests = $this->userQuestRepository->findByUser($user);
        $categoryQuests = [];

        foreach ($allUserQuests as $userQuest) {
            $questTemplate = $userQuest->getQuestTemplate();
            if ($questTemplate && $questTemplate->getCategory() === $category) {
                if (!$userQuest->isRewardClaimed()) {
                    $categoryQuests[] = $userQuest;
                }
            }
        }

        if ($categoryQuests === []) {
            return null;
        }

        $completedUnclaimed = array_filter($categoryQuests, static function ($q) {
            return $q->isCompleted() && !$q->isRewardClaimed();
        });

        if ($completedUnclaimed !== []) {
            return reset($completedUnclaimed);
        }

        usort($categoryQuests, static function ($a, $b) {
            $aValue = $a->getQuestTemplate()->getTargetValue();
            $bValue = $b->getQuestTemplate()->getTargetValue();

            return $aValue <=> $bValue;
        });

        $firstIncomplete = array_filter($categoryQuests, static function ($q) {
            return !$q->isCompleted();
        });

        if ($firstIncomplete !== []) {
            return reset($firstIncomplete);
        }

        return null;
    }
}
