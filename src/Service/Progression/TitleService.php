<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Dungeon\DungeonCatalog;
use App\Entity\PlayerTitle;
use App\Entity\User;
use App\Entity\UserTitle;
use App\Enum\TitleUnlockType;
use App\Exception\BusinessRuleException;
use App\Repository\PlayerTitleRepository;
use App\Repository\UserBestiaryEntryRepository;
use App\Repository\UserDungeonProgressRepository;
use App\Repository\UserTitleRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class TitleService
{
    private const EQUIPMENT_FULL_TARGET = 1;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PlayerTitleRepository $playerTitleRepository,
        private readonly UserTitleRepository $userTitleRepository,
        private readonly UserDungeonProgressRepository $dungeonProgressRepository,
        private readonly UserBestiaryEntryRepository $bestiaryEntryRepository,
    ) {
    }

    public function syncUnlocks(User $user): void
    {
        $unlockedMap = $this->userTitleRepository->getUnlockedMapForUser($user);
        $dungeonProgress = $this->dungeonProgressRepository->getProgressMapForUser($user);
        $now = new \DateTimeImmutable();

        foreach ($this->playerTitleRepository->findAllOrdered() as $title) {
            if (isset($unlockedMap[$title->getCode()])) {
                continue;
            }

            $evaluation = $this->evaluateUnlock($user, $title, $dungeonProgress);
            if (!$evaluation['met']) {
                continue;
            }

            $userTitle = new UserTitle();
            $userTitle->setUser($user);
            $userTitle->setPlayerTitle($title);
            $userTitle->setUnlockedAt($now);
            $this->entityManager->persist($userTitle);
            $unlockedMap[$title->getCode()] = $userTitle;
        }

        $this->entityManager->flush();
    }

    /**
     * @return array{equippedTitleCode: string|null, titles: list<array<string, mixed>>}
     */
    public function getTitlesForUser(User $user): array
    {
        $this->syncUnlocks($user);

        $unlockedMap = $this->userTitleRepository->getUnlockedMapForUser($user);
        $dungeonProgress = $this->dungeonProgressRepository->getProgressMapForUser($user);
        $equipped = $user->getEquippedTitle();

        $titles = [];
        foreach ($this->playerTitleRepository->findAllOrdered() as $title) {
            $unlockedEntry = $unlockedMap[$title->getCode()] ?? null;
            $row = [
                'code' => $title->getCode(),
                'nameKey' => $title->getNameKey(),
                'descriptionKey' => $title->getDescriptionKey(),
                'unlocked' => $unlockedEntry !== null,
                'unlockedAt' => $unlockedEntry?->getUnlockedAt()?->format('Y-m-d H:i:s'),
            ];

            $evaluation = $this->evaluateUnlock($user, $title, $dungeonProgress);
            if ($evaluation['progress'] !== null) {
                $row['progress'] = $evaluation['progress'];
            }

            $titles[] = $row;
        }

        return [
            'equippedTitleCode' => $equipped?->getCode(),
            'titles' => $titles,
        ];
    }

    /**
     * @return array{equipped: bool, equippedTitleCode: string}
     */
    public function equipTitle(User $user, string $titleCode): array
    {
        $title = $this->playerTitleRepository->findOneByCode($titleCode);
        if ($title === null) {
            throw new BusinessRuleException('titleNotFound');
        }

        $this->syncUnlocks($user);

        $userTitle = $this->userTitleRepository->findOneForUserAndTitle($user, $title);
        if ($userTitle === null) {
            throw new BusinessRuleException('titleNotUnlocked');
        }

        $user->setEquippedTitle($title);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return [
            'equipped' => true,
            'equippedTitleCode' => $title->getCode(),
        ];
    }

    public function equipRookieIfNoneEquipped(User $user): void
    {
        if ($user->getEquippedTitle() !== null) {
            return;
        }

        $rookie = $this->playerTitleRepository->findOneByCode(TitleCodes::ROOKIE);
        if ($rookie === null) {
            return;
        }

        $userTitle = $this->userTitleRepository->findOneForUserAndTitle($user, $rookie);
        if ($userTitle === null) {
            return;
        }

        $user->setEquippedTitle($rookie);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    /**
     * @return array{code: string, nameKey: string}|null
     */
    public function buildEquippedTitleDto(?PlayerTitle $title): ?array
    {
        if ($title === null) {
            return null;
        }

        return [
            'code' => $title->getCode(),
            'nameKey' => $title->getNameKey(),
        ];
    }

    /**
     * @param array<string, int> $dungeonProgress
     *
     * @return array{
     *   met: bool,
     *   progress: array{current: int, target: int}|null
     * }
     */
    private function evaluateUnlock(User $user, PlayerTitle $title, array $dungeonProgress): array
    {
        $stats = $user->getUserStatistics();
        $unlockValue = (int) $title->getUnlockValue();
        $userLevel = UserLevelResolver::of($user);

        return match ($title->getUnlockType()) {
            TitleUnlockType::GAME_START => [
                'met' => true,
                'progress' => null,
            ],
            TitleUnlockType::LEVEL_REACHED => $this->snapshot($userLevel, $unlockValue),
            TitleUnlockType::GOLD_BALANCE => $this->snapshot((int) ($user->getGold() ?? 0), $unlockValue),
            TitleUnlockType::DUNGEON_COMPLETED => $this->snapshot(
                min(
                    $dungeonProgress[(string) $title->getUnlockDungeonId()] ?? 0,
                    DungeonCatalog::STAGES_PER_DUNGEON
                ),
                DungeonCatalog::STAGES_PER_DUNGEON,
                DungeonCatalog::isDungeonCompleted($dungeonProgress, (string) $title->getUnlockDungeonId()),
            ),
            TitleUnlockType::ITEMS_COLLECTED => $this->snapshot($stats?->getItemsCollected() ?? 0, $unlockValue),
            TitleUnlockType::RARE_EQUIPMENT_FULL => $this->snapshot(
                $stats?->getRareEquipmentFullReached() ?? 0,
                self::EQUIPMENT_FULL_TARGET,
            ),
            TitleUnlockType::ALL_DUNGEONS_COMPLETED => $this->snapshot(
                DungeonCatalog::countCompletedDungeons($dungeonProgress),
                \count(DungeonCatalog::all()),
                DungeonCatalog::areAllDungeonsCompleted($dungeonProgress),
            ),
            TitleUnlockType::ALL_DUNGEONS_AND_LEVEL => DungeonCatalog::areAllDungeonsCompleted($dungeonProgress)
                ? $this->snapshot(min($userLevel, $unlockValue), $unlockValue)
                : $this->snapshot(
                    DungeonCatalog::countCompletedDungeons($dungeonProgress),
                    \count(DungeonCatalog::all()),
                    false,
                ),
            TitleUnlockType::LEGENDARY_ITEMS_COLLECTED => $this->snapshot(
                $stats?->getLegendaryItemsCollected() ?? 0,
                $unlockValue,
            ),
            TitleUnlockType::FIGHTS_WON => $this->snapshot($stats?->getFightsWon() ?? 0, $unlockValue),
            TitleUnlockType::BESTIARY_COMPLETE => $this->snapshot(
                $this->bestiaryEntryRepository->countForUser($user),
                $unlockValue,
            ),
            TitleUnlockType::EPIC_ITEMS_COLLECTED => $this->snapshot(
                $stats?->getEpicItemsCollected() ?? 0,
                $unlockValue,
            ),
            TitleUnlockType::EPIC_EQUIPMENT_FULL => $this->snapshot(
                $stats?->getEpicEquipmentFullReached() ?? 0,
                self::EQUIPMENT_FULL_TARGET,
            ),
            TitleUnlockType::LEGENDARY_EQUIPMENT_FULL => $this->snapshot(
                $stats?->getLegendaryEquipmentFullReached() ?? 0,
                self::EQUIPMENT_FULL_TARGET,
            ),
        };
    }

    /**
     * @return array{met: bool, progress: array{current: int, target: int}}
     */
    private function snapshot(int $current, int $target, ?bool $met = null): array
    {
        return [
            'met' => $met ?? ($current >= $target),
            'progress' => [
                'current' => $current,
                'target' => $target,
            ],
        ];
    }
}
