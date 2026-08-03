<?php

declare(strict_types=1);

namespace App\Service\Ship;

use App\Entity\ShipMessage;
use App\Infrastructure\WebSocket\WebSocketPublisher;
use Psr\Log\LoggerInterface;

class ShipChatNotifier
{
    public function __construct(
        private WebSocketPublisher $webSocketPublisher,
        private LoggerInterface $logger,
    ) {
    }

    public function buildShipChatPayload(ShipMessage $message): array
    {
        $ship = $message->getShip();
        $author = $message->getAuthor();

        if ($ship === null) {
            throw new \LogicException('ShipMessage without ship cannot be broadcast');
        }

        $payload = [
            'type' => 'ship-chat-message',
            'id' => $message->getId(),
            'shipId' => $ship->getId(),
            'content' => $message->getContent() ?? '',
            'createdAt' => $message->getCreatedAt()?->format(\DATE_ATOM) ?? (new \DateTimeImmutable())->format(\DATE_ATOM),
            'isSystem' => $message->isSystem(),
        ];

        if ($author !== null) {
            $payload['author'] = [
                'id' => $author->getId(),
                'username' => $author->getUsername(),
            ];
        }

        if ($message->isSystem()) {
            $payload['shipTreasury'] = [
                'gold' => $ship->getGold(),
                'diamonds' => $ship->getDiamonds(),
            ];
        }

        return $payload;
    }

    public function publishMessage(ShipMessage $message): void
    {
        $ship = $message->getShip();

        if ($ship === null) {
            $this->logger->warning('ship.chat.publishSkippedNoShip', [
                'messageId' => $message->getId(),
            ]);

            return;
        }

        try {
            $this->webSocketPublisher->broadcastToShip($ship->getId(), $this->buildShipChatPayload($message));
        } catch (\Throwable $e) {
            $this->logger->warning('ship.chat.httpBroadcastFailed', [
                'shipId' => $ship->getId(),
                'messageId' => $message->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
