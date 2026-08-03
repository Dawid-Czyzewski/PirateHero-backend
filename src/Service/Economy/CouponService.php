<?php

declare(strict_types=1);

namespace App\Service\Economy;

use App\Dto\Coupon\CouponHistoryEntry;
use App\Dto\Coupon\RedeemCouponResult;
use App\Entity\Coupon;
use App\Entity\User;
use App\Entity\UserBooster;
use App\Entity\UserCoupon;
use App\Enum\CouponRewardType;
use App\Enum\CouponType;
use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Repository\CouponRepository;
use App\Repository\UserCouponRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

readonly class CouponService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CouponRepository $couponRepository,
        private readonly UserCouponRepository $userCouponRepository,
        private readonly WearableRewardFactory $wearableRewardFactory,
    ) {
    }

    public function redeemCoupon(User $user, string $code): RedeemCouponResult
    {
        $normalizedCode = trim($code);
        if ($normalizedCode == '') {
            throw new BusinessRuleException('couponCodeRequired');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $coupon = $this->couponRepository->findByCodeForUpdate($normalizedCode);
            if (!$coupon) {
                throw new ResourceNotFoundException('couponCodeNotFound');
            }

            $lockedUser = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if ($lockedUser === null) {
                throw new ResourceNotFoundException('userNotFound');
            }

            if ($coupon->isExpired()) {
                throw new BusinessRuleException('couponExpired');
            }

            if ($coupon->getType() === CouponType::SINGLE_USE) {
                if ($coupon->isUsed()) {
                    throw new BusinessRuleException('couponAlreadyUsed');
                }
            } else {
                $existingUserCoupon = $this->userCouponRepository->findByUserAndCoupon($lockedUser, $coupon);
                if ($existingUserCoupon !== null) {
                    throw new BusinessRuleException('couponAlreadyUsedByYou');
                }
            }

            $rewardReceived = $this->applyReward($lockedUser, $coupon);

            $userCoupon = new UserCoupon();
            $userCoupon->setUser($lockedUser);
            $userCoupon->setCoupon($coupon);
            $userCoupon->setRewardReceived($rewardReceived);
            $this->entityManager->persist($userCoupon);
            $lockedUser->addUserCoupon($userCoupon);

            if ($coupon->getType() === CouponType::SINGLE_USE) {
                $coupon->setUsedByUser($lockedUser);
                $coupon->setUsedAt(new \DateTime());
                $this->entityManager->persist($coupon);
            }

            $this->entityManager->persist($lockedUser);
            $this->entityManager->flush();
            $connection->commit();

            return new RedeemCouponResult(
                success: true,
                reward: $rewardReceived,
                code: (string) $coupon->getCode(),
                rewardType: $coupon->getRewardType()->value,
            );
        } catch (UniqueConstraintViolationException) {
            $connection->rollBack();
            throw new BusinessRuleException('couponAlreadyUsedByYou');
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    private function applyReward(User $user, Coupon $coupon): array
    {
        $rewardType = $coupon->getRewardType();
        $rewardValue = $coupon->getRewardValue();

        return match ($rewardType) {
            null => throw new BusinessRuleException('couponRewardTypeMissing'),
            CouponRewardType::GOLD => $this->applyGoldReward($user, $rewardValue),
            CouponRewardType::diamonds => $this->applydiamondsReward($user, $rewardValue),
            CouponRewardType::BOOSTER => $this->applyBoosterReward($user, $coupon),
            CouponRewardType::ITEM => $this->applyItemReward($user, $this->validateItemCouponRewardData($coupon->getRewardData())),
        };
    }

    private function validateItemCouponRewardData(?array $rewardData): array
    {
        if ($rewardData === null) {
            throw new BusinessRuleException('invalidItemCouponRewardData');
        }

        $rarityRaw = $rewardData['rarity'] ?? null;
        $typeRaw = $rewardData['type'] ?? null;
        if (!\is_string($rarityRaw) || trim($rarityRaw) === '' || !\is_string($typeRaw) || trim($typeRaw) === '') {
            throw new BusinessRuleException('invalidItemCouponRewardData');
        }

        if (WearableItemRarity::tryFrom(strtoupper(trim($rarityRaw))) === null) {
            throw new BusinessRuleException('invalidItemCouponRewardData');
        }

        try {
            WearableItemType::fromLegacyOrSelf(trim($typeRaw));
        } catch (\Throwable) {
            throw new BusinessRuleException('invalidItemCouponRewardData');
        }

        return $rewardData;
    }

    private function applyGoldReward(User $user, ?int $amount): array
    {
        if ($amount === null || $amount <= 0) {
            throw new BusinessRuleException('invalidGoldRewardAmount');
        }

        $user->addGold($amount);
        $this->entityManager->persist($user);

        return [
            'type' => 'GOLD',
            'amount' => $amount,
        ];
    }

    private function applydiamondsReward(User $user, ?int $amount): array
    {
        if ($amount === null || $amount <= 0) {
            throw new BusinessRuleException('invaliddiamondsRewardAmount');
        }

        $user->addDiamonds($amount);
        $this->entityManager->persist($user);

        return [
            'type' => 'diamonds',
            'amount' => $amount,
        ];
    }

    private function applyBoosterReward(User $user, Coupon $coupon): array
    {
        $boosterTemplate = $coupon->getBoosterTemplate();
        if ($boosterTemplate === null) {
            throw new BusinessRuleException('invalidBoosterRewardData');
        }

        $boosterTemplateId = (int) $boosterTemplate->getId();
        $durationDays = $coupon->getBoosterDurationDays() ?? 7;
        $userBooster = new UserBooster();
        $userBooster->setUser($user);
        $userBooster->setBoosterTemplate($boosterTemplate);
        $userBooster->setExpiresAt((new \DateTime())->modify("+{$durationDays} days"));

        $this->entityManager->persist($userBooster);
        $user->addUserBooster($userBooster);

        return [
            'type' => 'BOOSTER',
            'boosterTemplateId' => $boosterTemplateId,
            'boosterName' => $boosterTemplate->getName(),
            'durationDays' => $durationDays,
        ];
    }

    private function applyItemReward(User $user, array $rewardData): array
    {
        $storage = $user->getStorage();
        if (!$storage) {
            throw new ResourceNotFoundException('userStorageNotFound');
        }

        $freeSlot = $storage->getSlots()->filter(static fn ($s) => $s->getItem() === null)->first();
        if (!$freeSlot) {
            throw new BusinessRuleException('noFreeStorageSlot');
        }

        $item = $this->wearableRewardFactory->createFromRewardData($user, $rewardData);
        $freeSlot->setItem($item);

        $this->entityManager->persist($item);
        $this->entityManager->persist($freeSlot);

        $itemData = [
            'id' => $item->getId(),
            'name' => $item->getName(),
            'type' => $item->getType()->value,
            'rarity' => $item->getRarity()->value,
            'price' => $item->getPrice(),
        ];

        if ($item->getStatistics()) {
            $stats = $item->getStatistics();
            $itemData['statistics'] = $stats->toClientArray();
        }

        return [
            'type' => 'ITEM',
            'itemId' => $item->getId(),
            'itemName' => $item->getName(),
            'rarity' => $item->getRarity()->value,
            'itemType' => $item->getType()->value,
            'item' => $itemData,
        ];
    }

    public function getUserCouponHistory(User $user): array
    {
        $userCoupons = $this->userCouponRepository->findByUser($user);

        return array_map(static function (UserCoupon $userCoupon) {
            $coupon = $userCoupon->getCoupon();

            return new CouponHistoryEntry(
                id: (int) $userCoupon->getId(),
                code: (string) $coupon->getCode(),
                rewardType: $coupon->getRewardType()->value,
                rewardReceived: $userCoupon->getRewardReceived(),
                usedAt: $userCoupon->getUsedAt()->format('Y-m-d H:i:s'),
            );
        }, $userCoupons);
    }
}
