<?php

declare(strict_types=1);

namespace App\Service\Bestiary;

use App\Bestiary\BestiaryTrophyCatalog;
use App\Entity\User;
use App\Entity\UserBestiaryTrophy;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Repository\UserBestiaryEntryRepository;
use App\Repository\UserBestiaryTrophyRepository;
use App\Service\Progression\TitleService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class BestiaryTrophyService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserBestiaryEntryRepository $entryRepository,
        private readonly UserBestiaryTrophyRepository $trophyRepository,
        private readonly TitleService $titleService,
    ) {
    }

    public function sync(User $user): void
    {
        $count = $this->entryRepository->countForUser($user);
        $existing = $this->trophyRepository->getMapForUser($user);
        $changed = false;

        foreach (BestiaryTrophyCatalog::definitions() as $def) {
            if ($count < $def['targetCount']) {
                continue;
            }
            if (isset($existing[$def['code']])) {
                continue;
            }

            $row = new UserBestiaryTrophy();
            $row->setUser($user);
            $row->setTrophyCode($def['code']);
            $row->setUnlockedAt(new \DateTimeImmutable());
            $row->setRewardClaimed(false);
            $this->entityManager->persist($row);
            $existing[$def['code']] = $row;
            $changed = true;
        }

        if ($changed) {
            $this->entityManager->flush();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatus(User $user): array
    {
        $this->sync($user);

        $count = $this->entryRepository->countForUser($user);
        $map = $this->trophyRepository->getMapForUser($user);
        $level = $this->resolveLevel($user);
        $trophies = [];
        $unclaimed = 0;

        foreach (BestiaryTrophyCatalog::definitions() as $def) {
            $row = $map[$def['code']] ?? null;
            $unlocked = $row instanceof UserBestiaryTrophy;
            $claimed = $unlocked && $row->isRewardClaimed();
            $canClaim = $unlocked && !$claimed;
            if ($canClaim) {
                ++$unclaimed;
            }
            $reward = BestiaryTrophyCatalog::claimReward($def, $level);
            $trophies[] = [
                'code' => $def['code'],
                'targetCount' => $def['targetCount'],
                'unlocked' => $unlocked,
                'rewardClaimed' => $claimed,
                'canClaim' => $canClaim,
                'unlockedAt' => $unlocked ? $row->getUnlockedAt()->format(\DateTimeInterface::ATOM) : null,
                'rewards' => $reward,
                'titleRewardCode' => $def['titleCode'],
            ];
        }

        return [
            'discoveredCount' => $count,
            'total' => BestiaryTrophyCatalog::TOTAL_ENTRIES,
            'trophies' => $trophies,
            'unclaimedCount' => $unclaimed,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function claim(User $user, string $code): array
    {
        $def = BestiaryTrophyCatalog::find($code);
        if ($def === null) {
            throw new ResourceNotFoundException('bestiaryTrophyNotFound');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $locked = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$locked instanceof User) {
                throw new BusinessRuleException('userNotFound');
            }

            $this->sync($locked);
            $row = $this->trophyRepository->findOneForUserAndCode($locked, $code);
            if (!$row instanceof UserBestiaryTrophy) {
                throw new BusinessRuleException('bestiaryTrophyNotUnlocked');
            }
            if ($row->isRewardClaimed()) {
                throw new BusinessRuleException('bestiaryTrophyAlreadyClaimed');
            }

            $level = $this->resolveLevel($locked);
            $reward = BestiaryTrophyCatalog::claimReward($def, $level);
            $locked->addGold($reward['gold']);
            $locked->addDiamonds($reward['diamonds']);
            $row->setRewardClaimed(true);

            $titleGranted = false;
            $titleCode = $def['titleCode'];
            if ($titleCode !== null) {
                $titleGranted = $this->titleService->grantTitle($locked, $titleCode);
            }

            $this->entityManager->persist($locked);
            $this->entityManager->persist($row);
            $this->entityManager->flush();
            $connection->commit();

            return [
                'rewards' => $reward,
                'titleGranted' => $titleGranted,
                'titleCode' => $titleGranted ? $titleCode : null,
                'updatedUser' => $this->userSnapshot($locked),
                'status' => $this->getStatus($locked),
            ];
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
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
