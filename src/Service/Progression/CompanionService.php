<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Domain\Constants\CompanionConstants;
use App\Entity\CompanionTemplate;
use App\Entity\User;
use App\Entity\UserCompanion;
use App\Exception\BusinessRuleException;
use App\Repository\CompanionTemplateRepository;
use App\Repository\UserCompanionRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class CompanionService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CompanionTemplateRepository $companionTemplateRepository,
        private readonly UserCompanionRepository $userCompanionRepository,
        private readonly UserWriteLockExecutor $userWriteLockExecutor,
    ) {
    }

    /**
     * @return array{
     *   companions: list<array<string, mixed>>,
     *   equippedCode: string|null,
     *   abilityReady: bool,
     *   canFeed: bool,
     *   unclaimedCount: int
     * }
     */
    public function getStatus(User $user): array
    {
        $this->ensureParrotUnlock($user);

        return $this->buildStatusPayload($user);
    }

    /**
     * @return array<string, mixed>
     */
    public function feed(User $user, string $companionCode): array
    {
        return $this->userWriteLockExecutor->execute($user, function (User $lockedUser) use ($companionCode): array {
            $this->ensureParrotUnlock($lockedUser);

            $template = $this->requireTemplate($companionCode);
            $owned = $this->userCompanionRepository->findOneForUserAndTemplate($lockedUser, $template);
            if ($owned === null) {
                throw new BusinessRuleException('companionNotUnlocked');
            }

            if (!$this->isFeedReady($owned)) {
                throw new BusinessRuleException('companionFeedOnCooldown');
            }

            if ($owned->getLevel() >= CompanionConstants::MAX_LEVEL) {
                throw new BusinessRuleException('companionMaxLevel');
            }

            $lockedUser->spendGold(CompanionConstants::FEED_GOLD_COST);
            $owned->setLevel(min(CompanionConstants::MAX_LEVEL, $owned->getLevel() + 1));
            $owned->setLastFedAt(new \DateTimeImmutable());

            $this->entityManager->persist($owned);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();

            return $this->buildStatusPayload($lockedUser);
        });
    }

    /**
     * @return array{equipped: bool, equippedCode: string}
     */
    public function equip(User $user, string $companionCode): array
    {
        return $this->userWriteLockExecutor->execute($user, function (User $lockedUser) use ($companionCode): array {
            $this->ensureParrotUnlock($lockedUser);

            $template = $this->requireTemplate($companionCode);
            $owned = $this->userCompanionRepository->findOneForUserAndTemplate($lockedUser, $template);
            if ($owned === null) {
                throw new BusinessRuleException('companionNotUnlocked');
            }

            $lockedUser->setEquippedCompanion($template);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();

            return [
                'equipped' => true,
                'equippedCode' => $template->getCode(),
            ];
        });
    }

    /**
     * @return array{equipped: bool, equippedCode: null}
     */
    public function unequip(User $user): array
    {
        return $this->userWriteLockExecutor->execute($user, function (User $lockedUser): array {
            $lockedUser->setEquippedCompanion(null);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();

            return [
                'equipped' => false,
                'equippedCode' => null,
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function useAbility(User $user): array
    {
        return $this->userWriteLockExecutor->execute($user, function (User $lockedUser): array {
            $this->ensureParrotUnlock($lockedUser);

            $equipped = $lockedUser->getEquippedCompanion();
            if ($equipped === null || $equipped->getCode() !== CompanionConstants::CODE_PARROT) {
                throw new BusinessRuleException('companionAbilityRequiresEquippedParrot');
            }

            $owned = $this->userCompanionRepository->findOneForUserAndTemplate($lockedUser, $equipped);
            if ($owned === null) {
                throw new BusinessRuleException('companionNotUnlocked');
            }

            if (!$this->isAbilityReady($owned)) {
                throw new BusinessRuleException('companionAbilityOnCooldown');
            }

            $activity = $lockedUser->getCurrentActivity();
            if ($activity === null || $activity->getMission() === null) {
                throw new BusinessRuleException('companionAbilityRequiresActiveMission');
            }

            $startTime = $activity->getStartTime();
            if ($startTime === null) {
                throw new BusinessRuleException('companionAbilityRequiresActiveMission');
            }

            $now = new \DateTime();
            $newStart = (clone \DateTime::createFromInterface($startTime))
                ->modify('-'.CompanionConstants::ABILITY_SHORTEN_MISSION_MINUTES.' minutes');
            if ($newStart > $now) {
                $newStart = $now;
            }
            $activity->setStartTime($newStart);

            $owned->setLastAbilityAt(new \DateTimeImmutable());

            $this->entityManager->persist($activity);
            $this->entityManager->persist($owned);
            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();

            return $this->buildStatusPayload($lockedUser);
        });
    }

    public function workGoldBonusPercentForUser(User $user): int
    {
        $equipped = $user->getEquippedCompanion();
        if ($equipped === null) {
            return 0;
        }

        $owned = $this->userCompanionRepository->findOneForUserAndTemplate($user, $equipped);
        if ($owned === null) {
            return 0;
        }

        return CompanionConstants::workGoldBonusPercent($owned->getLevel());
    }

    public function ensureParrotUnlock(User $user): void
    {
        if (UserLevelResolver::of($user) < CompanionConstants::UNLOCK_LEVEL) {
            return;
        }

        $template = $this->companionTemplateRepository->findOneByCode(CompanionConstants::CODE_PARROT);
        if ($template === null) {
            return;
        }

        $existing = $this->userCompanionRepository->findOneForUserAndTemplate($user, $template);
        if ($existing !== null) {
            return;
        }

        $userCompanion = new UserCompanion();
        $userCompanion->setUser($user);
        $userCompanion->setCompanionTemplate($template);
        $userCompanion->setUnlockedAt(new \DateTimeImmutable());
        $userCompanion->setLevel(1);
        $this->entityManager->persist($userCompanion);
        $this->entityManager->flush();
    }

    /**
     * @return array{
     *   companions: list<array<string, mixed>>,
     *   equippedCode: string|null,
     *   abilityReady: bool,
     *   canFeed: bool,
     *   unclaimedCount: int
     * }
     */
    private function buildStatusPayload(User $user): array
    {
        $ownedMap = $this->userCompanionRepository->getOwnedMapForUser($user);
        $equipped = $user->getEquippedCompanion();
        $equippedCode = $equipped?->getCode();

        $companions = [];
        $abilityReady = false;
        $canFeed = false;
        $ownsParrot = false;

        foreach ($this->companionTemplateRepository->findAllOrdered() as $template) {
            $owned = $ownedMap[$template->getCode()] ?? null;
            $unlocked = $owned !== null;
            $level = $owned?->getLevel() ?? 0;
            $feedReady = $unlocked && $this->isFeedReady($owned);
            $abilityOk = $unlocked && $this->isAbilityReady($owned);

            if ($template->getCode() === CompanionConstants::CODE_PARROT && $unlocked) {
                $ownsParrot = true;
                $abilityReady = $abilityOk;
                $canFeed = $feedReady;
            }

            $companions[] = [
                'code' => $template->getCode(),
                'nameKey' => $template->getNameKey(),
                'descriptionKey' => $template->getDescriptionKey(),
                'imageKey' => $template->getImageKey(),
                'unlocked' => $unlocked,
                'unlockedAt' => $owned?->getUnlockedAt()?->format('Y-m-d H:i:s'),
                'level' => $level,
                'lastFedAt' => $owned?->getLastFedAt()?->format('Y-m-d H:i:s'),
                'lastAbilityAt' => $owned?->getLastAbilityAt()?->format('Y-m-d H:i:s'),
                'canFeed' => $feedReady,
                'abilityReady' => $abilityOk,
                'workGoldBonusPercent' => CompanionConstants::workGoldBonusPercent($level),
            ];
        }

        return [
            'companions' => $companions,
            'equippedCode' => $equippedCode,
            'abilityReady' => $abilityReady,
            'canFeed' => $canFeed,
            'unclaimedCount' => ($abilityReady && $ownsParrot) ? 1 : 0,
        ];
    }

    private function requireTemplate(string $code): CompanionTemplate
    {
        $template = $this->companionTemplateRepository->findOneByCode($code);
        if ($template === null) {
            throw new BusinessRuleException('companionNotFound');
        }

        return $template;
    }

    private function isFeedReady(?UserCompanion $owned): bool
    {
        if ($owned === null) {
            return false;
        }

        if ($owned->getLevel() >= CompanionConstants::MAX_LEVEL) {
            return false;
        }

        $lastFedAt = $owned->getLastFedAt();
        if ($lastFedAt === null) {
            return true;
        }

        $readyAt = $lastFedAt->modify('+'.CompanionConstants::FEED_COOLDOWN_HOURS.' hours');

        return $readyAt <= new \DateTimeImmutable();
    }

    private function isAbilityReady(?UserCompanion $owned): bool
    {
        if ($owned === null) {
            return false;
        }

        $lastAbilityAt = $owned->getLastAbilityAt();
        if ($lastAbilityAt === null) {
            return true;
        }

        $readyAt = $lastAbilityAt->modify('+'.CompanionConstants::ABILITY_COOLDOWN_HOURS.' hours');

        return $readyAt <= new \DateTimeImmutable();
    }
}
