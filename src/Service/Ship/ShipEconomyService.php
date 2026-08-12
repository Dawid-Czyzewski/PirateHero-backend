<?php

declare(strict_types=1);

namespace App\Service\Ship;

use App\Domain\Constants\ShipConstants;
use App\Entity\Ship;
use App\Entity\ShipMember;
use App\Entity\ShipMessage;
use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Repository\ShipMemberRepository;
use App\Service\Progression\DailyChallengeService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

readonly class ShipEconomyService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShipMemberRepository $shipMemberRepository,
        private ShipChatService $shipChatService,
        private ShipUpgradePricingService $shipUpgradePricingService,
        private DailyChallengeService $dailyChallengeService,
    ) {
    }

    public function depositToShip(Ship $ship, User $user, ?int $gold = null, ?int $diamonds = null): ?ShipMessage
    {
        $this->entityManager->beginTransaction();
        try {
            $this->entityManager->lock($user, LockMode::PESSIMISTIC_WRITE);
            $this->entityManager->lock($ship, LockMode::PESSIMISTIC_WRITE);

            $member = $this->shipMemberRepository->findOneBy(['user' => $user, 'ship' => $ship]);
            if (!$member instanceof ShipMember) {
                throw new OperationForbiddenException('shipMembershipRequired');
            }

            $gold = $gold ?? 0;
            $diamonds = $diamonds ?? 0;

            if ($gold < 0 || $diamonds < 0) {
                throw new BusinessRuleException('negativeDepositNotAllowed');
            }
            if ($gold === 0 && $diamonds === 0) {
                throw new BusinessRuleException('nothingToDeposit');
            }

            if ($gold > 0) {
                $user->spendGold($gold);
                $this->dailyChallengeService->recordGoldSpent($user, $gold);
            }
            if ($diamonds > 0) {
                $user->spendDiamonds($diamonds);
            }

            $ship->addGold($gold);
            $ship->addDiamonds($diamonds);
            $member->addGoldContribution($gold);
            $member->addDiamondsContribution($diamonds);

            $this->entityManager->flush();
            $this->entityManager->commit();

            return $this->shipChatService->publishDepositSystemMessage($ship, $user, $gold, $diamonds);
        } catch (\Throwable $e) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }

            throw $e;
        }
    }

    public function upgradeShip(Ship $ship, string $upgradeType): array
    {
        $this->entityManager->beginTransaction();
        try {
            $this->entityManager->lock($ship, LockMode::PESSIMISTIC_WRITE);

            $maxForType = 'hull' === $upgradeType ? ShipConstants::MAX_HULL_UPGRADE_LEVEL : ShipConstants::MAX_UPGRADE_LEVEL;
            $currentLevel = match ($upgradeType) {
                'skills' => $ship->getSkillsUpgrade(),
                'work' => $ship->getWorkUpgrade(),
                'missions' => $ship->getMissionsUpgrade(),
                'hull' => $ship->getHullUpgrade(),
                default => throw new BusinessRuleException('invalidUpgradeType'),
            };

            if ($currentLevel >= $maxForType) {
                throw new BusinessRuleException('maxUpgradeLevelReached');
            }

            $nextLevel = $currentLevel + 1;
            $cost = $this->shipUpgradePricingService->getCostForTargetLevel($upgradeType, $nextLevel);
            $goldCost = $cost['gold'];
            $diamondsCost = $cost['diamonds'];

            if ($ship->getGold() < $goldCost) {
                throw new BusinessRuleException('insufficientShipGold');
            }
            if ($ship->getDiamonds() < $diamondsCost) {
                throw new BusinessRuleException('insufficientShipDiamonds');
            }

            $ship->setGold($ship->getGold() - $goldCost);
            $ship->setDiamonds($ship->getDiamonds() - $diamondsCost);

            match ($upgradeType) {
                'skills' => $ship->setSkillsUpgrade($nextLevel),
                'work' => $ship->setWorkUpgrade($nextLevel),
                'missions' => $ship->setMissionsUpgrade($nextLevel),
                'hull' => $this->applyHullUpgrade($ship, $nextLevel),
            };

            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->shipChatService->publishUpgradeSystemMessage($ship, $upgradeType, $nextLevel);

            return [
                'upgradeType' => $upgradeType,
                'newLevel' => $nextLevel,
                'goldCost' => $goldCost,
                'diamondsCost' => $diamondsCost,
            ];
        } catch (\Throwable $e) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }

            throw $e;
        }
    }

    private function applyHullUpgrade(Ship $ship, int $nextHullLevel): void
    {
        $ship->setHullUpgrade($nextHullLevel);
        $ship->setMaxMembers(ShipConstants::BASE_CREW_SLOTS + $nextHullLevel);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildDepositResponse(Ship $ship, User $user, ?ShipMessage $systemMessage): array
    {
        $this->entityManager->refresh($user);
        $this->entityManager->refresh($ship);

        $data = [
            'ship' => [
                'id' => $ship->getId(),
                'gold' => $ship->getGold(),
                'diamonds' => $ship->getDiamonds(),
            ],
            'user' => [
                'gold' => $user->getGold(),
                'diamonds' => $user->getDiamonds(),
            ],
        ];

        if ($systemMessage instanceof ShipMessage) {
            $data['shipMessage'] = [
                'id' => $systemMessage->getId(),
                'content' => $systemMessage->getContent() ?? '',
                'createdAt' => $systemMessage->getCreatedAt()?->format(\DATE_ATOM) ?? (new \DateTimeImmutable())->format(\DATE_ATOM),
                'isSystem' => $systemMessage->isSystem(),
                'shipTreasury' => [
                    'gold' => $ship->getGold(),
                    'diamonds' => $ship->getDiamonds(),
                ],
            ];
        }

        return $data;
    }

    /**
     * @param array{upgradeType: string, newLevel: int, goldCost: int, diamondsCost: int} $result
     *
     * @return array<string, mixed>
     */
    public function buildUpgradeResponse(Ship $ship, array $result): array
    {
        $this->entityManager->refresh($ship);

        return [
            'upgradeType' => $result['upgradeType'],
            'newLevel' => $result['newLevel'],
            'goldCost' => $result['goldCost'],
            'diamondsCost' => $result['diamondsCost'],
            'ship' => [
                'id' => $ship->getId(),
                'gold' => $ship->getGold(),
                'diamonds' => $ship->getDiamonds(),
                'skillsUpgrade' => $ship->getSkillsUpgrade(),
                'workUpgrade' => $ship->getWorkUpgrade(),
                'missionsUpgrade' => $ship->getMissionsUpgrade(),
                'hullUpgrade' => $ship->getHullUpgrade(),
                'maxMembers' => $ship->getMaxMembers(),
            ],
        ];
    }
}
