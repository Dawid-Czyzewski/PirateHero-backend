<?php

declare(strict_types=1);

namespace App\Service\Ship;

use App\Entity\Ship;
use App\Entity\ShipMember;
use App\Entity\User;
use App\Enum\ShipRole;
use Doctrine\ORM\EntityManagerInterface;

class ShipMembershipMutationHelper
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShipChatService $shipChatService,
    ) {
    }

    public function addMember(Ship $ship, User $user): ShipMember
    {
        $member = new ShipMember();
        $member->setUser($user);
        $member->setShip($ship);
        $member->setRole(ShipRole::MEMBER);
        $member->setJoinedAt(new \DateTimeImmutable());

        $this->entityManager->persist($member);

        $this->shipChatService->addSystemMessage($ship, 'shipPage.chatSystem.memberJoined', [
            'name' => $user->getUsername() ?? '',
        ]);

        return $member;
    }
}
