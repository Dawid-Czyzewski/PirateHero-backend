<?php

declare(strict_types=1);

namespace App\Service\Ship;

use App\Entity\ShipInvitation;
use App\Entity\ShipJoinRequest;
use App\Entity\User;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Repository\ShipFightNotificationRepository;
use App\Repository\ShipInvitationRepository;
use App\Repository\ShipJoinRequestRepository;
use App\Repository\ShipRemovalNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class ShipNotificationService
{
    public function __construct(
        private ShipRemovalNotificationRepository $shipRemovalNotificationRepository,
        private ShipFightNotificationRepository $shipFightNotificationRepository,
        private ShipInvitationRepository $shipInvitationRepository,
        private ShipJoinRequestRepository $shipJoinRequestRepository,
        private ShipMembershipService $shipMembershipService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function markInvitationAsRead(User $user, int $invitationId): void
    {
        $invitation = $this->shipInvitationRepository->find($invitationId);
        if (!$invitation instanceof ShipInvitation || $invitation->getUser()->getId() !== $user->getId()) {
            throw new ResourceNotFoundException('shipInvitationNotFound');
        }

        $invitation->setIsRead(true);
        $this->entityManager->persist($invitation);
        $this->entityManager->flush();
    }

    public function markJoinRequestAsRead(User $user, int $requestId): void
    {
        $member = $this->shipMembershipService->getShipMember($user);
        if (!$member || !$member->isOwner()) {
            throw new OperationForbiddenException('shipOwnerRequired');
        }

        $ship = $member->getShip();
        $joinRequest = $this->shipJoinRequestRepository->find($requestId);
        if (!$joinRequest instanceof ShipJoinRequest || $joinRequest->getShip()->getId() !== $ship->getId()) {
            throw new ResourceNotFoundException('shipJoinRequestNotFound');
        }

        $joinRequest->setIsRead(true);
        $this->entityManager->persist($joinRequest);
        $this->entityManager->flush();
    }

    public function markRemovalNotificationAsRead(User $user, int $notificationId): void
    {
        $notification = $this->shipRemovalNotificationRepository->find($notificationId);
        if (!$notification || $notification->getUser()->getId() !== $user->getId()) {
            throw new ResourceNotFoundException('removalNotificationNotFound');
        }

        $notification->setIsRead(true);
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    public function markFightNotificationAsRead(User $user, int $notificationId): void
    {
        $notification = $this->shipFightNotificationRepository->find($notificationId);
        if (!$notification || $notification->getUser()->getId() !== $user->getId()) {
            throw new ResourceNotFoundException('fightNotificationNotFound');
        }

        $notification->setIsRead(true);
        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }
}
