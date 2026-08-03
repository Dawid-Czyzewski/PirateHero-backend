<?php

declare(strict_types=1);

namespace App\Service\Economy;

use App\Domain\Constants\EconomyConstants;
use App\Entity\User;
use App\Entity\UserAvailableBooster;
use App\Entity\UserBooster;
use App\Enum\BoosterType;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Repository\BoosterTemplateRepository;
use App\Repository\UserAvailableBoosterRepository;
use App\Repository\UserBoosterRepository;
use App\Service\Random\RandomizerInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BoosterService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private BoosterTemplateRepository $boosterTemplateRepository,
        private UserAvailableBoosterRepository $userAvailableBoosterRepository,
        private UserBoosterRepository $userBoosterRepository,
        private LoggerInterface $logger,
        private ?RandomizerInterface $randomizer = null,
    ) {
    }

    public function generateAvailableBoostersForUser(User $user): void
    {
        $boosterTypes = BoosterType::cases();

        foreach ($boosterTypes as $type) {
            for ($tier = 1; $tier <= 3; ++$tier) {
                $template = $this->boosterTemplateRepository->findOneBy([
                    'type' => $type,
                    'tier' => $tier,
                ]);

                if (!$template) {
                    continue;
                }

                $useGold = $tier <= EconomyConstants::BOOSTER_GOLD_MAX_TIER;
                $userLevel = (int) $user->getLevel()->getName();

                $basePrice = $this->getBasePrice($type, $tier, $useGold, $userLevel);

                if ($useGold) {
                    $priceVariation = $this->randomInt(
                        -EconomyConstants::BOOSTER_PRICE_JITTER_PERCENT,
                        EconomyConstants::BOOSTER_PRICE_JITTER_PERCENT,
                    );
                    $price = max(1, (int) round($basePrice * (1 + $priceVariation / 100)));
                } else {
                    $price = EconomyConstants::BOOSTER_DIAMOND_FALLBACK_PRICE;
                }

                $userAvailableBooster = new UserAvailableBooster();
                $userAvailableBooster->setUser($user);
                $userAvailableBooster->setBoosterTemplate($template);
                $userAvailableBooster->setPrice($price);
                $userAvailableBooster->setUseGold($useGold);

                $this->entityManager->persist($userAvailableBooster);
            }
        }

        $this->entityManager->flush();
    }

    public function buyBooster(User $user, int $userAvailableBoosterId): void
    {
        $em = $this->entityManager;
        $connection = $em->getConnection();
        $connection->beginTransaction();
        try {
            $lockedUser = $em->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if ($lockedUser === null) {
                throw new ResourceNotFoundException('userNotFound');
            }

            $userAvailableBooster = $this->userAvailableBoosterRepository->find($userAvailableBoosterId);

            if (!$userAvailableBooster) {
                throw new ResourceNotFoundException('boosterOfferNotFound');
            }

            if ($userAvailableBooster->getUser()->getId() !== $lockedUser->getId()) {
                throw new OperationForbiddenException('boosterOfferNotOwned');
            }

            $template = $userAvailableBooster->getBoosterTemplate();
            $price = $userAvailableBooster->getPrice();

            $activeBooster = $this->userBoosterRepository->findActiveBoosterByUserAndType(
                $lockedUser->getId(),
                $template->getType()
            );

            if ($activeBooster) {
                $oldTemplate = $activeBooster->getBoosterTemplate();

                $oldTier = $oldTemplate->getTier();
                $oldUseGold = $oldTier <= EconomyConstants::BOOSTER_GOLD_MAX_TIER;
                $userLevel = (int) $lockedUser->getLevel()->getName();
                $oldBasePrice = $this->getBasePrice($oldTemplate->getType(), $oldTier, $oldUseGold, $userLevel);

                if ($oldUseGold) {
                    $priceVariation = $this->randomInt(
                        -EconomyConstants::BOOSTER_PRICE_JITTER_PERCENT,
                        EconomyConstants::BOOSTER_PRICE_JITTER_PERCENT,
                    );
                    $oldPrice = max(1, (int) round($oldBasePrice * (1 + $priceVariation / 100)));
                } else {
                    $oldPrice = EconomyConstants::BOOSTER_DIAMOND_FALLBACK_PRICE;
                }

                $returnedUserAvailableBooster = new UserAvailableBooster();
                $returnedUserAvailableBooster->setUser($lockedUser);
                $returnedUserAvailableBooster->setBoosterTemplate($oldTemplate);
                $returnedUserAvailableBooster->setPrice($oldPrice);
                $returnedUserAvailableBooster->setUseGold($oldUseGold);

                $em->persist($returnedUserAvailableBooster);
                $em->remove($activeBooster);
            }

            if ($userAvailableBooster->isUseGold()) {
                $lockedUser->spendGold($price);
            } else {
                $lockedUser->spendDiamonds($price);
            }

            $userBooster = new UserBooster();
            $userBooster->setUser($lockedUser);
            $userBooster->setBoosterTemplate($template);
            $userBooster->setExpiresAt((new \DateTime())->modify('+7 days'));

            $em->remove($userAvailableBooster);

            $em->persist($userBooster);
            $em->persist($lockedUser);
            $em->flush();
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function useBooster(User $user, int $userBoosterId): void
    {
        $userBooster = $this->userBoosterRepository->find($userBoosterId);

        if (!$userBooster) {
            throw new ResourceNotFoundException('userBoosterNotFound');
        }

        if ($userBooster->getUser()->getId() !== $user->getId()) {
            throw new OperationForbiddenException('userBoosterNotOwned');
        }

        if ($userBooster->isExpired()) {
            throw new BusinessRuleException('boosterExpired');
        }

        $template = $userBooster->getBoosterTemplate();
        $effectAmount = $template->getEffectAmount();

        $this->applyBoosterEffect($user, $template->getType(), $effectAmount);

        $this->entityManager->remove($userBooster);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }

    private function applyBoosterEffect(User $user, BoosterType $type, int $effectAmount): void
    {
        switch ($type) {
            case BoosterType::ENERGY:
                $userCapacities = $user->getUserCapacities();
                if ($userCapacities) {
                    $currentEnergy = $userCapacities->getEnergyPoints() ?? 0;
                    $maxEnergy = $user->getEnergyPoints() ?? 100;
                    $newEnergy = min($currentEnergy + $effectAmount, $maxEnergy);
                    $userCapacities->setEnergyPoints($newEnergy);
                }
                break;

            case BoosterType::TRAINING_POINTS:
                $user->addTrainingPoints($effectAmount);
                break;

            case BoosterType::DUEL_POINTS:
                $userCapacities = $user->getUserCapacities();
                if ($userCapacities) {
                    $currentFightPoints = $userCapacities->getFightPoints() ?? 0;
                    $userCapacities->setFightPoints($currentFightPoints + $effectAmount);
                }
                break;
        }
    }

    public function getAvailableBoostersForUser(User $user): array
    {
        return $this->userAvailableBoosterRepository->findBy([
            'user' => $user,
        ]);
    }

    private function getBasePrice(BoosterType $type, int $tier, bool $useGold, int $userLevel = 1): int
    {
        if ($useGold) {
            $pricesForType = EconomyConstants::BOOSTER_GOLD_PRICES_BY_TYPE[$type->value];
            if (!isset($pricesForType[$tier - 1])) {
                throw new BusinessRuleException('boosterPriceNotConfigured');
            }

            $basePrice = $pricesForType[$tier - 1];
            $priceMultiplier = 1 + (($userLevel - 1) * EconomyConstants::BOOSTER_PRICE_LEVEL_STEP);

            return (int) round($basePrice * $priceMultiplier);
        }

        return EconomyConstants::BOOSTER_DIAMOND_FALLBACK_PRICE;
    }

    public function cleanupExpiredBoostersAndGenerateNew(User $user): void
    {
        $now = new \DateTime();

        $expiredBoosters = $this->userBoosterRepository->findExpiredBoostersByUser(
            $user->getId(),
            $now
        );

        if ($expiredBoosters === []) {
            return;
        }

        $userLevel = (int) $user->getLevel()->getName();

        foreach ($expiredBoosters as $expiredBooster) {
            $template = $expiredBooster->getBoosterTemplate();
            if (!$template) {
                $this->entityManager->remove($expiredBooster);
                continue;
            }

            $type = $template->getType();
            $tier = $template->getTier();

            $existingAvailableBooster = $this->userAvailableBoosterRepository->findByUserAndTemplate(
                $user->getId(),
                $template->getId()
            );

            if (!$existingAvailableBooster) {
                $useGold = $tier <= EconomyConstants::BOOSTER_GOLD_MAX_TIER;
                $basePrice = $this->getBasePrice($type, $tier, $useGold, $userLevel);

                if ($useGold) {
                    $priceVariation = $this->randomInt(
                        -EconomyConstants::BOOSTER_PRICE_JITTER_PERCENT,
                        EconomyConstants::BOOSTER_PRICE_JITTER_PERCENT,
                    );
                    $price = max(1, (int) round($basePrice * (1 + $priceVariation / 100)));
                } else {
                    $price = EconomyConstants::BOOSTER_DIAMOND_FALLBACK_PRICE;
                }

                $newUserAvailableBooster = new UserAvailableBooster();
                $newUserAvailableBooster->setUser($user);
                $newUserAvailableBooster->setBoosterTemplate($template);
                $newUserAvailableBooster->setPrice($price);
                $newUserAvailableBooster->setUseGold($useGold);

                $this->entityManager->persist($newUserAvailableBooster);
            }

            $this->entityManager->remove($expiredBooster);
        }

        $this->entityManager->flush();
    }

    public function calculateActualCapacity(User $user): void
    {
        try {
            $now = new \DateTime();

            $activeBoosters = $this->userBoosterRepository->findActiveBoostersByUser(
                $user->getId(),
                $now
            );

            $baseEnergyCapacity = EconomyConstants::BASE_ENERGY_CAPACITY;
            $baseTrainingCapacity = EconomyConstants::BASE_TRAINING_CAPACITY;
            $baseFightCapacity = EconomyConstants::BASE_FIGHT_CAPACITY;

            $energyBoost = 0;
            $trainingBoost = 0;
            $fightBoost = 0;

            foreach ($activeBoosters as $userBooster) {
                $template = $userBooster->getBoosterTemplate();
                if (!$template) {
                    continue;
                }

                $type = $template->getType();
                $effectAmount = $template->getEffectAmount();

                switch ($type) {
                    case BoosterType::ENERGY:
                        $energyBoost = max($energyBoost, $effectAmount);
                        break;
                    case BoosterType::TRAINING_POINTS:
                        $trainingBoost = max($trainingBoost, $effectAmount);
                        break;
                    case BoosterType::DUEL_POINTS:
                        $fightBoost = max($fightBoost, $effectAmount);
                        break;
                }
            }

            $userCapacities = $user->getUserCapacities();
            if ($userCapacities) {
                $userCapacities->setEnergyPoints($baseEnergyCapacity + $energyBoost);
                $userCapacities->setTrainingPoints($baseTrainingCapacity + $trainingBoost);
                $userCapacities->setFightPoints($baseFightCapacity + $fightBoost);
            }
        } catch (\Throwable $e) {
            $this->logger->warning('booster.calculateActualCapacity.failed', [
                'exception' => $e,
                'userId' => $user->getId(),
            ]);
            $userCapacities = $user->getUserCapacities();
            if ($userCapacities) {
                $userCapacities->setEnergyPoints(EconomyConstants::BASE_ENERGY_CAPACITY);
                $userCapacities->setTrainingPoints(EconomyConstants::BASE_TRAINING_CAPACITY);
                $userCapacities->setFightPoints(EconomyConstants::BASE_FIGHT_CAPACITY);
            }
        }
    }

    private function randomInt(int $min, int $max): int
    {
        if ($this->randomizer !== null) {
            return $this->randomizer->int($min, $max);
        }

        return random_int($min, $max);
    }
}
