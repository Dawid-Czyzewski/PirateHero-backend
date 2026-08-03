<?php

declare(strict_types=1);

namespace App\Service\Ship;

use App\Entity\Ship;
use App\Entity\ShipMessage;
use App\Entity\User;
use App\Repository\ShipMessageRepository;
use Doctrine\ORM\EntityManagerInterface;

class ShipChatService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShipMessageRepository $shipMessageRepository,
        private ShipChatNotifier $shipChatNotifier,
    ) {
    }

    /**
     * @param bool $notifySubscribers gdy `false`, nie wywołuje HTTP broadcast (używane z procesu Ratchet — tam emit jest in-process)
     */
    public function addMessage(Ship $ship, User $user, string $content, bool $notifySubscribers = true): ShipMessage
    {
        $message = new ShipMessage();
        $message->setShip($ship);
        $message->setAuthor($user);
        $message->setContent($content);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        if ($notifySubscribers) {
            try {
                $this->shipChatNotifier->publishMessage($message);
            } catch (\Throwable) {
            }
        }

        return $message;
    }

    public function addSystemMessage(Ship $ship, string $translationKey, array $params = []): ShipMessage
    {
        $content = json_encode([
            'key' => $translationKey,
            'params' => $params,
        ], \JSON_THROW_ON_ERROR);

        $message = new ShipMessage();
        $message->setShip($ship);
        $message->setAuthor(null);
        $message->setContent($content);
        $message->setIsSystem(true);

        $this->entityManager->persist($message);
        $this->entityManager->flush();

        try {
            $this->shipChatNotifier->publishMessage($message);
        } catch (\Throwable) {
        }

        return $message;
    }

    public function publishDepositSystemMessage(Ship $ship, User $user, int $gold, int $diamonds): ?ShipMessage
    {
        if ($gold <= 0 && $diamonds <= 0) {
            return null;
        }

        $name = $user->getUsername() ?? '';
        if ($gold > 0 && $diamonds > 0) {
            return $this->addSystemMessage($ship, 'shipPage.chatSystem.depositBoth', [
                'name' => $name,
                'gold' => $gold,
                'diamonds' => $diamonds,
            ]);
        }

        if ($gold > 0) {
            return $this->addSystemMessage($ship, 'shipPage.chatSystem.depositGold', [
                'name' => $name,
                'amount' => $gold,
            ]);
        }

        return $this->addSystemMessage($ship, 'shipPage.chatSystem.depositDiamonds', [
            'name' => $name,
            'amount' => $diamonds,
        ]);
    }

    public function publishUpgradeSystemMessage(Ship $ship, string $upgradeType, int $newLevel): void
    {
        $key = match ($upgradeType) {
            'skills' => 'shipPage.chatSystem.upgradeSkills',
            'work' => 'shipPage.chatSystem.upgradeWork',
            'missions' => 'shipPage.chatSystem.upgradeMissions',
            'hull' => 'shipPage.chatSystem.upgradeHull',
            default => 'shipPage.chatSystem.upgradeGeneric',
        };

        if ($key === 'shipPage.chatSystem.upgradeGeneric') {
            $this->addSystemMessage($ship, $key, [
                'type' => $upgradeType,
                'level' => $newLevel,
            ]);

            return;
        }

        $this->addSystemMessage($ship, $key, ['level' => $newLevel]);
    }

    public function getMessages(Ship $ship, int $limit = 50): array
    {
        return $this->shipMessageRepository->createQueryBuilder('m')
            ->where('m.ship = :ship')
            ->setParameter('ship', $ship)
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
