<?php

declare(strict_types=1);

namespace App\Service\Ship;

use App\Entity\Ship;
use App\Entity\ShipInvitation;
use App\Entity\ShipMember;
use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Repository\ShipInvitationRepository;
use App\Repository\ShipJoinRequestRepository;
use App\Repository\ShipMemberRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class ShipInvitationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShipMemberRepository $shipMemberRepository,
        private ShipJoinRequestRepository $shipJoinRequestRepository,
        private ShipInvitationRepository $shipInvitationRepository,
        private UserRepository $userRepository,
        private ShipMembershipMutationHelper $shipMembershipMutationHelper,
    ) {
    }

    public function inviteMember(Ship $ship, User $inviter, string $username): ShipInvitation
    {
        $inviterMember = $this->shipMemberRepository->findOneBy(['user' => $inviter, 'ship' => $ship]);
        if (!$inviterMember || !$inviterMember->isOwner()) {
            throw new OperationForbiddenException('shipOwnerRequired');
        }

        $currentMembersCount = $this->shipMemberRepository->count(['ship' => $ship]);
        if ($currentMembersCount >= $ship->getMaxMembers()) {
            throw new BusinessRuleException('shipMaxMembersReached');
        }

        $userToInvite = $this->userRepository->findOneBy(['username' => $username]);
        if (!$userToInvite) {
            throw new ResourceNotFoundException('userNotFound');
        }

        $existingMember = $this->shipMemberRepository->findOneBy(['user' => $userToInvite]);
        if ($existingMember) {
            throw new BusinessRuleException('alreadyInShip');
        }

        $existingInvitation = $this->shipInvitationRepository->findOneBy([
            'user' => $userToInvite,
            'ship' => $ship,
            'accepted' => null,
        ]);
        if ($existingInvitation) {
            throw new BusinessRuleException('shipInvitationAlreadySent');
        }

        $handledInvitation = $this->shipInvitationRepository->findOneBy([
            'user' => $userToInvite,
            'ship' => $ship,
        ]);
        if ($handledInvitation && $handledInvitation->isAccepted() !== null) {
            $this->entityManager->remove($handledInvitation);
            $this->entityManager->flush();
        }

        $invitation = new ShipInvitation();
        $invitation->setUser($userToInvite);
        $invitation->setShip($ship);
        $invitation->setInviter($inviter);

        $this->entityManager->persist($invitation);
        $this->entityManager->flush();

        return $invitation;
    }

    public function cancelInvitation(Ship $ship, User $inviter, User $invitedUser): void
    {
        $inviterMember = $this->shipMemberRepository->findOneBy(['user' => $inviter, 'ship' => $ship]);
        if (!$inviterMember || !$inviterMember->isOwner()) {
            throw new OperationForbiddenException('shipOwnerRequired');
        }

        $invitation = $this->shipInvitationRepository->findOneBy([
            'user' => $invitedUser,
            'ship' => $ship,
            'accepted' => null,
        ]);

        if (!$invitation) {
            throw new ResourceNotFoundException('shipInvitationNotFound');
        }

        $this->entityManager->remove($invitation);
        $this->entityManager->flush();
    }

    public function acceptInvitation(User $user, int $invitationId): ShipMember
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $this->entityManager->lock($user, LockMode::PESSIMISTIC_WRITE);
            $invitation = $this->shipInvitationRepository->find($invitationId);
            if (!$invitation) {
                throw new ResourceNotFoundException('shipInvitationNotFound');
            }

            if ($invitation->getUser()->getId() !== $user->getId()) {
                throw new BusinessRuleException('shipInvitationNotForUser');
            }

            if ($invitation->isAccepted() !== null) {
                throw new BusinessRuleException('shipInvitationAlreadyHandled');
            }

            $ship = $invitation->getShip();
            $this->entityManager->lock($ship, LockMode::PESSIMISTIC_WRITE);

            $existingMember = $this->shipMemberRepository->findOneBy(['user' => $user]);
            if ($existingMember) {
                throw new BusinessRuleException('alreadyInShip');
            }

            $currentMembersCount = $this->shipMemberRepository->count(['ship' => $ship]);
            if ($currentMembersCount >= $ship->getMaxMembers()) {
                throw new BusinessRuleException('shipMaxMembersReached');
            }

            $member = $this->shipMembershipMutationHelper->addMember($ship, $user);

            $invitation->setAccepted(true);

            $otherInvitations = $this->shipInvitationRepository->findBy([
                'user' => $user,
                'ship' => $ship,
                'accepted' => null,
            ]);
            foreach ($otherInvitations as $otherInv) {
                if ($otherInv->getId() !== $invitation->getId()) {
                    $otherInv->setAccepted(true);
                    $this->entityManager->persist($otherInv);
                }
            }

            $pendingJoinRequest = $this->shipJoinRequestRepository->findOneBy([
                'user' => $user,
                'ship' => $ship,
                'approved' => null,
            ]);
            if ($pendingJoinRequest) {
                $pendingJoinRequest->setApproved(false);
                $this->entityManager->persist($pendingJoinRequest);
            }

            $this->entityManager->persist($invitation);
            $this->entityManager->flush();

            $connection->commit();

            return $member;
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function declineInvitation(User $user, int $invitationId): void
    {
        $invitation = $this->shipInvitationRepository->find($invitationId);
        if (!$invitation) {
            throw new ResourceNotFoundException('shipInvitationNotFound');
        }

        if ($invitation->getUser()->getId() !== $user->getId()) {
            throw new OperationForbiddenException('shipInvitationNotForUser');
        }

        if ($invitation->isAccepted() !== null) {
            throw new BusinessRuleException('shipInvitationAlreadyHandled');
        }

        $invitation->setAccepted(false);
        $this->entityManager->persist($invitation);
        $this->entityManager->flush();
    }

    public function getInvitationForUser(Ship $ship, User $user): ?ShipInvitation
    {
        return $this->shipInvitationRepository->findOneBy([
            'user' => $user,
            'ship' => $ship,
            'accepted' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildInvitationSentResponse(ShipInvitation $invitation): array
    {
        return [
            'invitation' => [
                'id' => $invitation->getId(),
                'createdAt' => $invitation->getCreatedAt()->format('c'),
                'user' => [
                    'id' => $invitation->getUser()->getId(),
                    'username' => $invitation->getUser()->getUsername(),
                ],
                'inviter' => [
                    'id' => $invitation->getInviter()->getId(),
                    'username' => $invitation->getInviter()->getUsername(),
                ],
            ],
        ];
    }
}
