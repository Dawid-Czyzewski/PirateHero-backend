<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Entity\User;
use App\Entity\UserQuest;
use App\Entity\WearableItem;
use App\Enum\QuestRewardType;
use App\Enum\WearableItemRarity;
use App\Service\Economy\WearableRewardFactory;
use App\Service\User\LevelService;

final readonly class QuestRewardClaimer
{
    public function __construct(
        private LevelService $levelService,
        private WearableRewardFactory $wearableRewardFactory,
    ) {
    }

    public function applyRewards(User $user, UserQuest $userQuest): QuestRewardApplicationResult
    {
        $template = $userQuest->getQuestTemplate();
        $rewardType = $template->getRewardType();
        $rewardAmount = $template->getRewardAmount();
        $levelUpData = null;
        $randomItem = null;

        switch ($rewardType) {
            case QuestRewardType::EXPERIENCE:
                $user->addExperiencePoints($rewardAmount);
                $levelUpData = $this->levelService->checkAndUpdateLevel($user);
                break;
            case QuestRewardType::GOLD:
                $user->addGold($rewardAmount);
                break;
            case QuestRewardType::diamonds:
                $user->addDiamonds($rewardAmount);
                break;
            case QuestRewardType::ITEM:
                $allowedRarities = $template->getCode() === 'fight_veteran_500'
                    ? [WearableItemRarity::RARE, WearableItemRarity::EPIC]
                    : null;
                $randomItem = $this->createRandomItemForUser($user, $allowedRarities);
                $this->wearableRewardFactory->placeInStorage($user, $randomItem);
                break;
        }

        $secondaryRewardType = $template->getSecondaryRewardType();
        $secondaryRewardAmount = $template->getSecondaryRewardAmount();
        if ($secondaryRewardType !== null && $secondaryRewardAmount !== null && $secondaryRewardAmount > 0) {
            switch ($secondaryRewardType) {
                case QuestRewardType::EXPERIENCE:
                    $user->addExperiencePoints($secondaryRewardAmount);
                    $secondaryLevelUp = $this->levelService->checkAndUpdateLevel($user);
                    if ($secondaryLevelUp['levelUp']) {
                        $levelUpData = $secondaryLevelUp;
                    }
                    break;
                case QuestRewardType::GOLD:
                    $user->addGold($secondaryRewardAmount);
                    break;
                case QuestRewardType::diamonds:
                    $user->addDiamonds($secondaryRewardAmount);
                    break;
                case QuestRewardType::ITEM:
                    $bonusItem = $this->createRandomItemForUser($user);
                    $this->wearableRewardFactory->placeInStorage($user, $bonusItem);
                    break;
            }
        }

        return new QuestRewardApplicationResult(
            rewardType: $rewardType,
            rewardAmount: $rewardAmount,
            levelUpData: $levelUpData,
            randomItem: $randomItem,
        );
    }

    /**
     * @param list<WearableItemRarity>|null $allowedRarities
     */
    private function createRandomItemForUser(User $user, ?array $allowedRarities = null): WearableItem
    {
        return $this->wearableRewardFactory->createRandomForUser($user, $allowedRarities);
    }
}
