<?php

declare(strict_types=1);

namespace App\Service\ShopBoosters;

use App\Entity\ShopBooster;
use App\Entity\User;
use App\Entity\UserShopBoosterSession;
use App\Enum\ShopBoosterCategory;
use App\Enum\ShopBoosterCurrency;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Repository\ShopBoosterRepository;
use App\Repository\UserShopBoosterSessionRepository;
use App\Service\Economy\BoosterService;
use App\Service\Progression\DailyChallengeService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class ShopBoosterSessionService implements CombatStatisticsProvider
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShopBoosterRepository $shopBoosterRepository,
        private UserShopBoosterSessionRepository $sessionRepository,
        private BoosterService $boosterService,
        private ShopBoosterEffectParser $effectParser,
        private DailyChallengeService $dailyChallengeService,
    ) {
    }

    /**
     * @return list<array{id: string, name: string, description: string, effect: string, durationHours: int, price: int, currency: string, multiplier: string, category: string}>
     *                                                                                                                                                                            name / description — klucze i18n (frontendowe `shopBooster.catalog.{id}.*`); effect — tylko do parsowania (+N% / pkt treningu).
     */
    public function getCatalogForApi(): array
    {
        $rows = [];
        foreach ($this->shopBoosterRepository->findAllOrdered() as $b) {
            $rows[] = [
                'id' => $b->getPublicCode(),
                'name' => $b->getName(),
                'description' => $b->getDescription(),
                'effect' => $b->getEffect(),
                'durationHours' => $b->getDurationHours(),
                'price' => $b->getPrice(),
                'currency' => $b->getCurrency()->value,
                'multiplier' => '',
                'category' => $b->getCategory()->value,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{boosterId: string, expiresAt: int}>
     */
    public function pruneExpiredSessions(User $user): void
    {
        $now = new \DateTimeImmutable();
        if ($this->sessionRepository->deleteExpiredForUser($user, $now) > 0) {
            $this->entityManager->flush();
        }
    }

    public function getMissionShopBoosterFraction(User $user): float
    {
        $now = new \DateTimeImmutable();
        foreach ($this->sessionRepository->findNonExpiredForUserInCategory($user, ShopBoosterCategory::Missions, $now) as $session) {
            $b = $session->getShopBooster();
            if ($b !== null) {
                return $this->effectParser->parseFirstPercentFraction($b->getEffect());
            }
        }

        return 0.0;
    }

    public function getWorkShopBoosterFraction(User $user): float
    {
        $now = new \DateTimeImmutable();
        foreach ($this->sessionRepository->findNonExpiredForUserInCategory($user, ShopBoosterCategory::Work, $now) as $session) {
            $b = $session->getShopBooster();
            if ($b !== null) {
                return $this->effectParser->parseFirstPercentFraction($b->getEffect());
            }
        }

        return 0.0;
    }

    public function getSkillsShopBoosterFraction(User $user): float
    {
        $now = new \DateTimeImmutable();
        foreach ($this->sessionRepository->findNonExpiredForUserInCategory($user, ShopBoosterCategory::Skills, $now) as $session) {
            $b = $session->getShopBooster();
            if ($b !== null) {
                return $this->effectParser->parseFirstPercentFraction($b->getEffect());
            }
        }

        return 0.0;
    }

    public function applySkillsBoosterToCombatStats(array $stats, float $skillsFraction): array
    {
        if ($skillsFraction <= 0.0) {
            return $stats;
        }

        $keys = ['health', 'strength', 'agility', 'luck', 'intelligence'];
        $out = $stats;
        foreach ($keys as $k) {
            if (!\array_key_exists($k, $out)) {
                continue;
            }
            $out[$k] = (int) floor((int) $out[$k] * (1 + $skillsFraction));
        }
        if (\array_key_exists('critical', $out)) {
            $out['critical'] = $out['luck'];
        }

        return $out;
    }

    public function getCombatStatistics(User $user): array
    {
        $this->pruneExpiredSessions($user);
        $p = $this->getSkillsShopBoosterFraction($user);

        return $this->applySkillsBoosterToCombatStats($user->getTotalStatistics(), $p);
    }

    public function buildActiveEntriesForUser(User $user): array
    {
        $now = new \DateTimeImmutable();
        $out = [];
        foreach ($this->sessionRepository->findNonExpiredByUser($user, $now) as $session) {
            $booster = $session->getShopBooster();
            if ($booster === null || $session->getExpiresAt() === null) {
                continue;
            }
            $out[] = [
                'boosterId' => $booster->getPublicCode(),
                'expiresAt' => $session->getExpiresAt()->getTimestamp() * 1000,
            ];
        }

        return $out;
    }

    public function purchase(User $user, string $boosterPublicCode): void
    {
        $code = trim($boosterPublicCode);
        if ($code === '') {
            throw new BusinessRuleException('shopBoosterIdRequired');
        }

        $def = null;
        foreach (ShopBoosterPublicCodeResolve::lookupCandidates($code) as $tryCode) {
            $def = $this->shopBoosterRepository->findOneByPublicCode($tryCode);
            if ($def !== null) {
                break;
            }
        }
        if ($def === null) {
            throw new ResourceNotFoundException('shopBoosterNotFound');
        }

        $em = $this->entityManager;
        $connection = $em->getConnection();
        $connection->beginTransaction();
        try {
            $lockedUser = $em->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if ($lockedUser === null) {
                throw new ResourceNotFoundException('userNotFound');
            }

            $this->pruneExpiredSessions($lockedUser);

            $this->boosterService->calculateActualCapacity($lockedUser);

            $now = new \DateTimeImmutable();
            $prevTrainingFlat = 0;
            if ($def->getCategory() === ShopBoosterCategory::Training) {
                foreach ($this->sessionRepository->findNonExpiredForUserInCategory($lockedUser, ShopBoosterCategory::Training, $now) as $old) {
                    $ob = $old->getShopBooster();
                    if ($ob !== null) {
                        $prevTrainingFlat = max($prevTrainingFlat, $this->effectParser->parseTrainingFlatBonus($ob->getEffect()));
                    }
                }
            }

            foreach ($this->sessionRepository->findNonExpiredForUserInCategory($lockedUser, $def->getCategory(), $now) as $old) {
                $em->remove($old);
            }

            $price = $def->getPrice();
            if ($def->getCurrency() === ShopBoosterCurrency::Gold) {
                $lockedUser->spendGold($price);
                $this->dailyChallengeService->recordGoldSpent($lockedUser, $price);
            } else {
                $lockedUser->spendDiamonds($price);
            }

            if ($def->getCategory() === ShopBoosterCategory::Training) {
                $newFlat = $this->effectParser->parseTrainingFlatBonus($def->getEffect());
                $maxBase = max(1, (int) ($lockedUser->getUserCapacities()?->getTrainingPoints() ?? 10));
                $netGrant = $newFlat - $prevTrainingFlat;
                $newMax = $maxBase + $newFlat;
                if ($netGrant >= 0) {
                    $lockedUser->addTrainingPoints($netGrant, $newMax);
                } else {
                    $cur = max(0, (int) ($lockedUser->getTrainingPoints() ?? 0));
                    $lockedUser->setTrainingPoints(min(max(0, $cur + $netGrant), $newMax));
                }
            }

            $expires = $now->modify(sprintf('+%d hours', max(1, $def->getDurationHours())));
            $session = new UserShopBoosterSession();
            $session->setUser($lockedUser);
            $session->setShopBooster($def);
            $session->setExpiresAt($expires);
            $em->persist($session);
            $em->persist($lockedUser);

            $em->flush();
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function seedCatalogIfEmpty(): void
    {
        if ($this->shopBoosterRepository->count([]) > 0) {
            return;
        }

        foreach (ShopBoosterCatalogDefinition::rows() as $row) {
            $e = new ShopBooster();
            $e->setPublicCode($row['publicCode']);
            $e->setCategory($row['category']);
            $e->setCurrency($row['currency']);
            $e->setPrice($row['price']);
            $e->setDurationHours($row['durationHours']);
            $e->setName($row['name']);
            $e->setDescription($row['description']);
            $e->setEffect($row['effect']);
            $e->setSortOrder($row['sortOrder']);
            $this->entityManager->persist($e);
        }

        $this->entityManager->flush();
    }
}
