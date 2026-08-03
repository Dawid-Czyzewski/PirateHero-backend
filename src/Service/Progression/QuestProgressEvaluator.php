<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Dungeon\DungeonCatalog;
use App\Entity\QuestTemplate;
use App\Entity\User;
use App\Entity\UserQuest;
use App\Entity\UserStatistics;
use App\Enum\QuestCategory;
use App\Repository\QuestTemplateRepository;
use App\Repository\UserBestiaryEntryRepository;
use App\Repository\UserDungeonProgressRepository;
use App\Repository\UserQuestRepository;
use App\Repository\UserTitleRepository;
use Doctrine\ORM\EntityManagerInterface;

final class QuestProgressEvaluator
{
    /** @var list<string> */
    private const DUNGEON_TITLE_CODES = [
        'crypt_hunter',
        'kraken_slayer',
        'fortress_raider',
        'volcanic_conqueror',
        'poseidon_champion',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserQuestRepository $userQuestRepository,
        private readonly QuestTemplateRepository $questTemplateRepository,
        private readonly UserDungeonProgressRepository $dungeonProgressRepository,
        private readonly UserBestiaryEntryRepository $bestiaryEntryRepository,
        private readonly UserTitleRepository $userTitleRepository,
    ) {
    }

    public function checkAndUpdateProgressForCategory(
        User $user,
        QuestCategory $category,
        UserStatistics $userStatistics,
    ): void {
        $templates = $this->questTemplateRepository->createQueryBuilder('qt')
            ->where('qt.category = :category')
            ->andWhere('qt.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        foreach ($templates as $template) {
            $this->updateUserQuestProgress($user, $template, $userStatistics);
        }
    }

    public function updateUserQuestProgress(User $user, QuestTemplate $template, UserStatistics $stats): void
    {
        $userQuest = $this->userQuestRepository->findByUserAndTemplate($user, $template->getId());

        if (!$userQuest) {
            $userQuest = new UserQuest();
            $userQuest->setUser($user);
            $userQuest->setQuestTemplate($template);
            $this->entityManager->persist($userQuest);
        }

        if ($userQuest->isCompleted() && $userQuest->isRewardClaimed()) {
            return;
        }

        $currentValue = $this->getCurrentValueForCategory($stats, $template, $user);

        $progress = min($currentValue, $template->getTargetValue());
        $userQuest->setCurrentProgress($progress);
    }

    public function getCurrentValueForCategory(UserStatistics $stats, QuestTemplate $template, User $user): int
    {
        $category = $template->getCategory();
        $dungeonProgress = $this->dungeonProgressRepository->getProgressMapForUser($user);

        return match ($category) {
            null => 0,
            QuestCategory::GOLD_SPENT => $stats->getGoldSpent(),
            QuestCategory::FIGHTS_WON => $stats->getFightsWon(),
            QuestCategory::FIGHTS_LOST => $stats->getFightsLost(),
            QuestCategory::LEVEL_UP => $stats->getLevelsReached(),
            QuestCategory::ITEMS_COLLECTED => $stats->getItemsCollected(),
            QuestCategory::RARE_ITEM_COLLECTED => $stats->getRareItemsCollected(),
            QuestCategory::LEGENDARY_ITEM_COLLECTED => $stats->getLegendaryItemsCollected(),
            QuestCategory::EPIC_ITEM_COLLECTED => $stats->getEpicItemsCollected(),
            QuestCategory::EQUIPMENT_FULL => $stats->getEquipmentFullReached(),
            QuestCategory::RARE_EQUIPMENT_FULL => $stats->getRareEquipmentFullReached(),
            QuestCategory::EPIC_EQUIPMENT_FULL => $stats->getEpicEquipmentFullReached(),
            QuestCategory::LEGENDARY_EQUIPMENT_FULL => $stats->getLegendaryEquipmentFullReached(),
            QuestCategory::ALL_DUNGEONS_COMPLETED => DungeonCatalog::areAllDungeonsCompleted($dungeonProgress) ? 1 : 0,
            QuestCategory::ALL_DUNGEONS_AND_LEVEL => $this->resolveAllDungeonsAndLevelProgress($user, $dungeonProgress),
            QuestCategory::DUNGEON_COMPLETED => DungeonCatalog::isDungeonCompleted(
                $dungeonProgress,
                (string) $template->getTargetDungeonId()
            ) ? 1 : 0,
            QuestCategory::BESTIARY_ENTRIES_DISCOVERED => $this->bestiaryEntryRepository->countForUser($user),
            QuestCategory::ALL_DUNGEON_TITLES_UNLOCKED => $this->countUnlockedDungeonTitles($user),
            QuestCategory::ALL_TITLES_UNLOCKED => \count($this->userTitleRepository->getUnlockedMapForUser($user)),
            QuestCategory::QUEST_LINE_COMPLETED => $this->isQuestLineCompleted($user, (string) $template->getTargetDungeonId()) ? 1 : 0,
        };
    }

    /**
     * @param array<string, int> $dungeonProgress
     */
    private function resolveAllDungeonsAndLevelProgress(User $user, array $dungeonProgress): int
    {
        if (!DungeonCatalog::areAllDungeonsCompleted($dungeonProgress)) {
            return 0;
        }

        $level = $user->getLevel();

        return $level !== null ? (int) $level->getName() : 1;
    }

    private function countUnlockedDungeonTitles(User $user): int
    {
        $unlockedMap = $this->userTitleRepository->getUnlockedMapForUser($user);
        $count = 0;
        foreach (self::DUNGEON_TITLE_CODES as $code) {
            if (isset($unlockedMap[$code])) {
                ++$count;
            }
        }

        return $count;
    }

    private function isQuestLineCompleted(User $user, string $lineCategory): bool
    {
        if ($lineCategory === '') {
            return false;
        }

        try {
            $category = QuestCategory::from($lineCategory);
        } catch (\ValueError) {
            return false;
        }

        $templates = $this->questTemplateRepository->createQueryBuilder('qt')
            ->where('qt.category = :category')
            ->andWhere('qt.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        if ($templates === []) {
            return false;
        }

        foreach ($templates as $template) {
            if (!$template instanceof QuestTemplate) {
                continue;
            }

            $userQuest = $this->userQuestRepository->findByUserAndTemplate($user, (int) $template->getId());
            if ($userQuest === null || !$userQuest->isCompleted()) {
                return false;
            }
        }

        return true;
    }
}
