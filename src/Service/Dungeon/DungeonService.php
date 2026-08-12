<?php

declare(strict_types=1);

namespace App\Service\Dungeon;

use App\Domain\Constants\DungeonConstants;
use App\Dungeon\DungeonCatalog;
use App\Dungeon\DungeonCompletionReward;
use App\Dungeon\DungeonCompletionRewardCalculator;
use App\Dungeon\DungeonStageReward;
use App\Dungeon\DungeonStageRewardCalculator;
use App\Entity\User;
use App\Entity\UserDungeonProgress;
use App\Entity\WearableItem;
use App\Enum\DungeonDifficulty;
use App\Enum\DungeonId;
use App\Exception\BusinessRuleException;
use App\Mapper\Api\DungeonMapper;
use App\Repository\UserDungeonProgressRepository;
use App\Service\Bestiary\BestiaryService;
use App\Service\Progression\QuestProgressService;
use App\Service\Progression\TimedActivityGuard;
use App\Service\Progression\TitleService;
use App\Service\ShopBoosters\ShopBoosterSessionService;
use App\Service\User\LevelService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class DungeonService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserDungeonProgressRepository $progressRepository,
        private readonly ShopBoosterSessionService $shopBoosterSessionService,
        private readonly DungeonBattleSimulator $battleSimulator,
        private readonly DungeonStageRewardCalculator $stageRewardCalculator,
        private readonly DungeonCompletionRewardCalculator $completionRewardCalculator,
        private readonly DungeonItemRewardFactory $itemRewardFactory,
        private readonly LevelService $levelService,
        private readonly TimedActivityGuard $timedActivityGuard,
        private readonly BestiaryService $bestiaryService,
        private readonly TitleService $titleService,
        private readonly QuestProgressService $questProgressService,
    ) {
    }

    /**
     * @return array{
     *     progress: array{normal: array<string, int>, hard: array<string, int>},
     *     playerStats: array{
     *         level: int,
     *         strength: int,
     *         agility: int,
     *         endurance: int,
     *         intelligence: int,
     *         luck: int
     *     },
     *     cooldownUntil: string|null,
     *     cooldownSecondsRemaining: int
     * }
     */
    public function getProgress(User $user): array
    {
        $this->timedActivityGuard->assertNoTimedActivity($user);
        $cooldown = $this->resolveCooldown($user);

        return DungeonMapper::progressResponse(
            $this->progressRepository->getProgressByDifficultyForUser($user),
            $this->buildPlayerCombatStats($user),
            $cooldown['cooldownUntil'],
            $cooldown['cooldownSecondsRemaining'],
        )->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function fightStage(
        User $user,
        string $dungeonIdRaw,
        int $stage,
        string $difficultyRaw = 'normal',
    ): array {
        $this->timedActivityGuard->assertNoTimedActivity($user);
        $this->assertDungeonCooldownClear($user);

        $dungeonId = DungeonId::tryFromString($dungeonIdRaw);
        if ($dungeonId === null) {
            throw new BusinessRuleException('dungeonNotFound');
        }

        $difficulty = DungeonDifficulty::tryFromString($difficultyRaw);
        if ($difficulty === null) {
            throw new BusinessRuleException('dungeonInvalidPayload');
        }

        $dungeon = DungeonCatalog::get($dungeonId);
        if ($dungeon === null) {
            throw new BusinessRuleException('dungeonNotFound');
        }

        if ($stage < 1 || $stage > DungeonCatalog::STAGES_PER_DUNGEON) {
            throw new BusinessRuleException('dungeonStageInvalid');
        }

        if ($this->resolvePlayerLevel($user) < $dungeon['reqLevel']) {
            throw new BusinessRuleException('dungeonLocked');
        }

        if ($difficulty === DungeonDifficulty::Hard) {
            $normalCleared = $this->progressRepository
                ->findOneForUserDungeon($user, $dungeonId->value, DungeonDifficulty::Normal->value)
                ?->getClearedStage() ?? 0;
            if ($normalCleared < DungeonCatalog::STAGES_PER_DUNGEON) {
                throw new BusinessRuleException('dungeonHardLocked');
            }
        }

        $progress = $this->progressRepository->findOneForUserDungeon(
            $user,
            $dungeonId->value,
            $difficulty->value,
        );
        $cleared = $progress?->getClearedStage() ?? 0;
        $isReplay = $difficulty === DungeonDifficulty::Hard
            && $cleared >= DungeonCatalog::STAGES_PER_DUNGEON;

        if ($isReplay) {
        } elseif ($stage !== $cleared + 1) {
            throw new BusinessRuleException('dungeonStageLocked');
        }

        $playerStats = $this->buildPlayerCombatStats($user);
        $opponent = DungeonCatalog::buildOpponent(
            $dungeonId,
            $stage,
            $dungeon['enemyNameKey'],
            $difficulty,
        );

        $rng = new Mulberry32Randomizer(DungeonCatalog::seed($dungeonId, $stage, $difficulty));
        $battle = $this->battleSimulator->simulate($playerStats, $opponent, $rng);

        $rewards = new DungeonStageReward(0, 0);
        $completionReward = null;
        $rewardItem = null;
        $dungeonCompleted = false;
        $updatedUser = null;

        if ($battle['won']) {
            $winPayload = $this->applyStageWin($user, $dungeonId, $stage, $progress, $difficulty, $isReplay);
            $rewards = $winPayload['rewards'];
            $completionReward = $winPayload['completionReward'];
            $rewardItem = $winPayload['rewardItem'];
            $dungeonCompleted = $winPayload['dungeonCompleted'];
            $updatedUser = $winPayload['updatedUser'];
        } else {
            $this->applyStageLoss($user, $difficulty);
        }

        $cooldown = $this->resolveCooldown($user);
        $battle['progress'] = $this->progressRepository->getProgressByDifficultyForUser($user);
        $battle['opponent'] = $opponent;
        $battle['rewards'] = $rewards->toArray();
        $battle['completionReward'] = $completionReward;
        $battle['dungeonCompleted'] = $dungeonCompleted;
        $battle['rewardItem'] = $rewardItem;
        $battle['updatedUser'] = $updatedUser;
        $battle['cooldownUntil'] = $cooldown['cooldownUntil'];
        $battle['cooldownSecondsRemaining'] = $cooldown['cooldownSecondsRemaining'];

        return DungeonMapper::fightStageResponse($battle)->toArray();
    }

    /**
     * @return array{
     *     rewards: DungeonStageReward,
     *     completionReward: array<string, mixed>|null,
     *     rewardItem: array<string, mixed>|null,
     *     dungeonCompleted: bool,
     *     updatedUser: array<string, mixed>|null
     * }
     */
    private function applyStageWin(
        User $user,
        DungeonId $dungeonId,
        int $stage,
        ?UserDungeonProgress $progress,
        DungeonDifficulty $difficulty,
        bool $isReplay,
    ): array {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $lockedUser = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$lockedUser instanceof User) {
                throw new BusinessRuleException('dungeonInvalidPayload');
            }

            $progress = $this->progressRepository->findOneForUserDungeon(
                $lockedUser,
                $dungeonId->value,
                $difficulty->value,
            );
            $cleared = $progress?->getClearedStage() ?? 0;
            $replayAllowed = $difficulty === DungeonDifficulty::Hard
                && $cleared >= DungeonCatalog::STAGES_PER_DUNGEON;

            if (!$replayAllowed && $stage !== $cleared + 1) {
                throw new BusinessRuleException('dungeonStageLocked');
            }

            if ($progress === null) {
                $progress = new UserDungeonProgress();
                $progress->setUser($lockedUser);
                $progress->setDungeonId($dungeonId->value);
                $progress->setDifficulty($difficulty->value);
                $this->entityManager->persist($progress);
            }

            if (!$replayAllowed) {
                $progress->setClearedStage($stage);
            }

            $lockedUser->setDungeonLostAt(null);
            $lockedUser->setDungeonLossCooldownSeconds(null);

            $rewards = $this->stageRewardCalculator->forStage($dungeonId, $stage, $difficulty);
            $userMutated = true;
            if (!$rewards->isEmpty()) {
                $lockedUser->addGold($rewards->gold);
                $lockedUser->addExperiencePoints($rewards->exp);
            }

            $completionReward = null;
            $rewardItem = null;
            $dungeonCompleted = false;
            $grantedCompletionItem = null;

            $isFinalStage = $stage === DungeonCatalog::STAGES_PER_DUNGEON;
            if (!$isReplay && $isFinalStage && !$progress->isCompletionRewardClaimed()) {
                $completion = $this->completionRewardCalculator->forDungeon($dungeonId, $difficulty);
                if (!$completion->isEmpty()) {
                    $grantedCompletionItem = $this->applyCompletionReward($lockedUser, $completion);
                    $progress->setCompletionRewardClaimed(true);
                    $dungeonCompleted = true;
                    $userMutated = true;
                    $completionReward = [
                        'gold' => $completion->gold,
                        'diamonds' => $completion->diamonds,
                        'item' => null,
                    ];
                }
            }

            $this->bestiaryService->recordDefeat($lockedUser, $dungeonId->value, $stage);

            if ($userMutated) {
                $this->levelService->checkAndUpdateLevel($lockedUser);
                $this->entityManager->persist($lockedUser);
            }

            $this->entityManager->flush();
            $connection->commit();

            $user->setDungeonLostAt(null);
            $user->setDungeonLossCooldownSeconds(null);

            if ($dungeonCompleted && $difficulty === DungeonDifficulty::Normal) {
                $this->titleService->syncUnlocks($lockedUser);
                $this->questProgressService->checkDungeonCompletedProgress($lockedUser);
            }

            $this->questProgressService->checkBestiaryProgress($lockedUser);

            if ($grantedCompletionItem instanceof WearableItem) {
                $itemPayload = $this->itemRewardFactory->itemToClientPayload($grantedCompletionItem);
                if ($completionReward !== null) {
                    $completionReward['item'] = $itemPayload;
                }
                $rewardItem = $itemPayload;
            }

            return [
                'rewards' => $rewards,
                'completionReward' => $completionReward,
                'rewardItem' => $rewardItem,
                'dungeonCompleted' => $dungeonCompleted,
                'updatedUser' => $userMutated ? $this->buildUpdatedUserSnapshot($lockedUser) : null,
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    private function applyStageLoss(User $user, DungeonDifficulty $difficulty): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $lockedUser = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$lockedUser instanceof User) {
                throw new BusinessRuleException('dungeonInvalidPayload');
            }

            $cooldownSeconds = $difficulty === DungeonDifficulty::Hard
                ? DungeonConstants::HARD_LOSS_COOLDOWN_SECONDS
                : DungeonConstants::LOSS_COOLDOWN_SECONDS;

            $lostAt = new \DateTimeImmutable();
            $lockedUser->setDungeonLostAt($lostAt);
            $lockedUser->setDungeonLossCooldownSeconds($cooldownSeconds);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();
            $connection->commit();

            $user->setDungeonLostAt($lostAt);
            $user->setDungeonLossCooldownSeconds($cooldownSeconds);
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    private function assertDungeonCooldownClear(User $user): void
    {
        $cooldown = $this->resolveCooldown($user);
        if ($cooldown['cooldownSecondsRemaining'] > 0) {
            throw new BusinessRuleException('dungeonCooldownActive');
        }
    }

    /**
     * @return array{cooldownUntil: string|null, cooldownSecondsRemaining: int}
     */
    private function resolveCooldown(User $user, ?\DateTimeImmutable $now = null): array
    {
        $lostAt = $user->getDungeonLostAt();
        if ($lostAt === null) {
            return [
                'cooldownUntil' => null,
                'cooldownSecondsRemaining' => 0,
            ];
        }

        $seconds = $user->getDungeonLossCooldownSeconds() ?? DungeonConstants::LOSS_COOLDOWN_SECONDS;
        $now ??= new \DateTimeImmutable();
        $until = \DateTimeImmutable::createFromInterface($lostAt)
            ->modify('+'.$seconds.' seconds');
        $remaining = $until->getTimestamp() - $now->getTimestamp();
        if ($remaining <= 0) {
            return [
                'cooldownUntil' => null,
                'cooldownSecondsRemaining' => 0,
            ];
        }

        return [
            'cooldownUntil' => $until->format(\DateTimeInterface::ATOM),
            'cooldownSecondsRemaining' => $remaining,
        ];
    }

    private function applyCompletionReward(User $user, DungeonCompletionReward $completion): ?WearableItem
    {
        if ($completion->gold > 0) {
            $user->addGold($completion->gold);
        }
        if ($completion->diamonds > 0) {
            $user->addDiamonds($completion->diamonds);
        }

        if (!$completion->grantsItem) {
            return null;
        }

        return $this->itemRewardFactory->grantRandomItem($user, $completion->itemRarity);
    }

    /**
     * @return array{
     *     gold: int,
     *     diamonds: int,
     *     experiencePoints: int,
     *     freeSkillPointsAvailable: int,
     *     level: array{name: string, expToNextLevel: int},
     *     storage?: array{id: int|string, slots: list<array<string, mixed>>}
     * }
     */
    private function buildUpdatedUserSnapshot(User $user): array
    {
        $level = $user->getLevel();
        $snapshot = [
            'gold' => (int) $user->getGold(),
            'diamonds' => (int) ($user->getDiamonds() ?? 0),
            'experiencePoints' => (int) $user->getExperiencePoints(),
            'freeSkillPointsAvailable' => (int) ($user->getFreeSkillPointsAvailable() ?? 0),
            'level' => [
                'name' => (string) ($level?->getName() ?? '1'),
                'expToNextLevel' => (int) ($level?->getExpToNextLevel() ?? 100),
            ],
        ];

        $storage = $this->itemRewardFactory->storageToClientPayload($user);
        if ($storage !== null) {
            $snapshot['storage'] = $storage;
        }

        return $snapshot;
    }

    /**
     * @return array{
     *     level: int,
     *     strength: int,
     *     agility: int,
     *     endurance: int,
     *     intelligence: int,
     *     luck: int
     * }
     */
    private function buildPlayerCombatStats(User $user): array
    {
        $combat = $this->shopBoosterSessionService->getCombatStatistics($user);

        return [
            'level' => $this->resolvePlayerLevel($user),
            'strength' => (int) $combat['strength'],
            'agility' => (int) $combat['agility'],
            'endurance' => max(1, (int) $combat['health']),
            'intelligence' => (int) $combat['intelligence'],
            'luck' => (int) $combat['luck'],
        ];
    }

    private function resolvePlayerLevel(User $user): int
    {
        $level = (int) ($user->getLevel()?->getName() ?? '1');

        return $level > 0 ? $level : 1;
    }
}
