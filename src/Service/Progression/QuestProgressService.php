<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Entity\User;
use App\Entity\UserStatistics;
use App\Enum\QuestCategory;
use App\Enum\WearableItemRarity;
use App\Repository\QuestTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class QuestProgressService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly QuestProgressEvaluator $questProgressEvaluator,
        private readonly UserQuestInitializer $userQuestInitializer,
        private readonly TitleService $titleService,
        private readonly QuestTemplateRepository $questTemplateRepository,
    ) {
    }

    public function recordItemCollected(User $user, WearableItemRarity $rarity): void
    {
        $userStatistics = $this->ensureUserStatistics($user);
        $userStatistics->incrementItemsCollected();

        if (\in_array($rarity, [
            WearableItemRarity::RARE,
            WearableItemRarity::EPIC,
            WearableItemRarity::LEGENDARY,
        ], true)) {
            $userStatistics->incrementRareItemsCollected();
            $this->reevaluateCategory($user, QuestCategory::RARE_ITEM_COLLECTED);
        }

        if ($rarity === WearableItemRarity::EPIC) {
            $userStatistics->incrementEpicItemsCollected();
            $this->reevaluateCategory($user, QuestCategory::EPIC_ITEM_COLLECTED);
        }

        if ($rarity === WearableItemRarity::LEGENDARY) {
            $userStatistics->incrementLegendaryItemsCollected();
            $this->reevaluateCategory($user, QuestCategory::LEGENDARY_ITEM_COLLECTED);
        }

        $this->reevaluateCategory($user, QuestCategory::ITEMS_COLLECTED);
        $this->titleService->syncUnlocks($user);
        $this->checkMetaCollectionProgress($user);
    }

    public function recordEquipmentFull(User $user): void
    {
        $this->recordEquipmentFullMilestone(
            $user,
            static fn (UserStatistics $s): int => $s->getEquipmentFullReached(),
            static function (UserStatistics $s): void {
                $s->markEquipmentFullReached();
            },
            QuestCategory::EQUIPMENT_FULL,
            syncTitles: false,
        );
    }

    public function recordRareEquipmentFull(User $user): void
    {
        $this->recordEquipmentFullMilestone(
            $user,
            static fn (UserStatistics $s): int => $s->getRareEquipmentFullReached(),
            static function (UserStatistics $s): void {
                $s->markRareEquipmentFullReached();
            },
            QuestCategory::RARE_EQUIPMENT_FULL,
            syncTitles: true,
        );
    }

    public function recordEpicEquipmentFull(User $user): void
    {
        $this->recordEquipmentFullMilestone(
            $user,
            static fn (UserStatistics $s): int => $s->getEpicEquipmentFullReached(),
            static function (UserStatistics $s): void {
                $s->markEpicEquipmentFullReached();
            },
            QuestCategory::EPIC_EQUIPMENT_FULL,
            syncTitles: true,
        );
    }

    public function recordLegendaryEquipmentFull(User $user): void
    {
        $this->recordEquipmentFullMilestone(
            $user,
            static fn (UserStatistics $s): int => $s->getLegendaryEquipmentFullReached(),
            static function (UserStatistics $s): void {
                $s->markLegendaryEquipmentFullReached();
            },
            QuestCategory::LEGENDARY_EQUIPMENT_FULL,
            syncTitles: true,
        );
    }

    /**
     * @param callable(UserStatistics): int $getReached
     * @param callable(UserStatistics): void $markReached
     */
    private function recordEquipmentFullMilestone(
        User $user,
        callable $getReached,
        callable $markReached,
        QuestCategory $category,
        bool $syncTitles,
    ): void {
        $userStatistics = $this->ensureUserStatistics($user);
        if ($getReached($userStatistics) >= 1) {
            return;
        }

        $markReached($userStatistics);
        $this->reevaluateCategory($user, $category);
        if ($syncTitles) {
            $this->titleService->syncUnlocks($user);
        }
    }

    public function checkAllDungeonsCompletedProgress(User $user): void
    {
        $this->reevaluateCategory($user, QuestCategory::ALL_DUNGEONS_COMPLETED);
        $this->reevaluateCategory($user, QuestCategory::ALL_DUNGEONS_AND_LEVEL);
    }

    public function checkDungeonCompletedProgress(User $user): void
    {
        $this->reevaluateCategoryWithoutFlush($user, QuestCategory::DUNGEON_COMPLETED);
        $this->checkAllDungeonsCompletedProgress($user);
        $this->titleService->syncUnlocks($user);
        $this->reevaluateCategoryWithoutFlush($user, QuestCategory::ALL_DUNGEON_TITLES_UNLOCKED);
        $this->entityManager->flush();
    }

    public function checkBestiaryProgress(User $user): void
    {
        $this->reevaluateCategoryWithoutFlush($user, QuestCategory::BESTIARY_ENTRIES_DISCOVERED);
        $this->titleService->syncUnlocks($user);
    }

    private function ensureUserStatistics(User $user): UserStatistics
    {
        $userStatistics = $user->getUserStatistics();
        if (!$userStatistics) {
            $userStatistics = new UserStatistics();
            $userStatistics->setUser($user);
            $user->setUserStatistics($userStatistics);
            $this->entityManager->persist($userStatistics);
        }

        return $userStatistics;
    }

    public function checkAndUpdateProgress(User $user, QuestCategory $category, int $amount = 1): void
    {
        $userStatistics = $this->ensureUserStatistics($user);

        $this->applyStatIncrement($userStatistics, $category, $amount);
        $this->questProgressEvaluator->checkAndUpdateProgressForCategory($user, $category, $userStatistics);
        $this->entityManager->flush();

        if ($category === QuestCategory::FIGHTS_WON) {
            $this->titleService->syncUnlocks($user);
            $this->checkMetaCollectionProgress($user);
        }
    }

    public function reevaluateCategory(User $user, QuestCategory $category): void
    {
        $this->reevaluateCategoryWithoutFlush($user, $category);
        $this->entityManager->flush();
    }

    private function reevaluateCategoryWithoutFlush(User $user, QuestCategory $category): void
    {
        $userStatistics = $this->ensureUserStatistics($user);
        $this->questProgressEvaluator->checkAndUpdateProgressForCategory($user, $category, $userStatistics);
    }

    private function checkMetaCollectionProgress(User $user): void
    {
        $userStatistics = $this->ensureUserStatistics($user);
        $this->questProgressEvaluator->checkAndUpdateProgressForCategory($user, QuestCategory::ALL_TITLES_UNLOCKED, $userStatistics);
        $this->questProgressEvaluator->checkAndUpdateProgressForCategory($user, QuestCategory::QUEST_LINE_COMPLETED, $userStatistics);
        $this->entityManager->flush();
    }

    private function applyStatIncrement(UserStatistics $stats, QuestCategory $category, int $amount): void
    {
        match ($category) {
            QuestCategory::GOLD_SPENT => $stats->addGoldSpent($amount),
            QuestCategory::FIGHTS_WON => $stats->incrementFightsWon(),
            QuestCategory::FIGHTS_LOST => $stats->incrementFightsLost(),
            default => null,
        };
    }

    public function updateUserLevel(User $user, int $levelNumber): void
    {
        $userStatistics = $this->ensureUserStatistics($user);

        $oldLevel = $userStatistics->getLevelsReached();
        $userStatistics->updateLevelsReached($levelNumber);

        if ($levelNumber > $oldLevel) {
            $templates = $this->questTemplateRepository->createQueryBuilder('qt')
                ->where('qt.category IN (:categories)')
                ->andWhere('qt.isActive = :active')
                ->setParameter('categories', [QuestCategory::LEVEL_UP, QuestCategory::ALL_DUNGEONS_AND_LEVEL])
                ->setParameter('active', true)
                ->getQuery()
                ->getResult();

            foreach ($templates as $template) {
                $this->questProgressEvaluator->updateUserQuestProgress($user, $template, $userStatistics);
            }

            $this->entityManager->flush();
            $this->titleService->syncUnlocks($user);
            $this->checkMetaCollectionProgress($user);
        }
    }

    public function initializeUserQuests(User $user): void
    {
        $this->userQuestInitializer->initializeUserQuests($user);
    }
}
