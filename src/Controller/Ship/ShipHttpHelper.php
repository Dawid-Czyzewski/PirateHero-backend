<?php

declare(strict_types=1);

namespace App\Controller\Ship;

use App\Entity\Ship;
use App\Entity\ShipMessage;
use App\Entity\User;
use App\Exception\OperationForbiddenException;
use App\Service\Ship\ShipMembershipService;

final readonly class ShipHttpHelper
{
    public function __construct(private ShipMembershipService $shipMembershipService)
    {
    }

    public function requireCurrentUserShip(User $user): Ship
    {
        $ship = $this->shipMembershipService->getShipForUser($user);
        if (null === $ship) {
            throw new OperationForbiddenException('shipMembershipRequired');
        }

        return $ship;
    }

    public static function shipMessageToArray(ShipMessage $message): array
    {
        $data = [
            'id' => $message->getId(),
            'content' => $message->getContent(),
            'createdAt' => $message->getCreatedAt()->format('c'),
            'isSystem' => $message->isSystem(),
        ];
        if (null !== $message->getAuthor()) {
            $data['author'] = [
                'id' => $message->getAuthor()->getId(),
                'username' => $message->getAuthor()->getUsername(),
            ];
        }

        return $data;
    }
}
