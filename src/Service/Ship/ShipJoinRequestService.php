<?php

declare(strict_types=1);

namespace App\Service\Ship;

use App\Entity\Ship;
use App\Entity\ShipJoinRequest;
use App\Entity\ShipMember;
use App\Entity\User;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Repository\ShipInvitationRepository;
use App\Repository\ShipJoinRequestRepository;
use App\Repository\ShipMemberRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class ShipJoinRequestService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShipMemberRepository $shipMemberRepository,
        private ShipJoinRequestRepository $shipJoinRequestRepository,
        private ShipInvitationRepository $shipInvitationRepository,
        private ShipMembershipMutationHelper $shipMembershipMutationHelper,
    ) {
    }

    public function requestToJoin(Ship $ship, User $user): ShipJoinRequest
    {
        $existingMember = $this->shipMemberRepository->findOneBy(['user' => $user]);
        if ($existingMember) {
            throw new BusinessRuleException('alreadyInShip');
        }

        $existingRequest = $this->shipJoinRequestRepository->findOneBy([
            'user' => $user,
            'ship' => $ship,
        ]);

        if ($existingRequest && $existingRequest->isApproved() === null) {
            throw new BusinessRuleException('shipJoinRequestAlreadySent');
        }

        if ($existingRequest && $existingRequest->isApproved() !== null) {
            $this->entityManager->remove($existingRequest);
            $this->entityManager->flush();
        }

        if ($ship->getMembers()->count() >= $ship->getMaxMembers()) {
            throw new BusinessRuleException('shipMaxMembersReached');
        }

        $request = new ShipJoinRequest();
        $request->setUser($user);
        $request->setShip($ship);

        $this->entityManager->persist($request);
        $this->entityManager->flush();

        return $request;
    }

    public function cancelJoinRequest(Ship $ship, User $user): void
    {
        $request = $this->shipJoinRequestRepository->findOneBy([
            'user' => $user,
            'ship' => $ship,
            'approved' => null,
        ]);

        if (!$request) {
            throw new ResourceNotFoundException('shipJoinRequestNotFound');
        }

        $this->entityManager->remove($request);
        $this->entityManager->flush();
    }

    public function getJoinRequestsForShip(Ship $ship, User $user): array
    {
        $member = $this->shipMemberRepository->findOneBy(['user' => $user, 'ship' => $ship]);
        if (!$member || !$member->isOwner()) {
            throw new OperationForbiddenException('shipOwnerRequired');
        }

        $requests = $this->shipJoinRequestRepository->findBy([
            'ship' => $ship,
        ], ['createdAt' => 'DESC']);

        $requestsData = [];
        foreach ($requests as $request) {
            $requestUser = $request->getUser();
            $requestsData[] = [
                'id' => $request->getId(),
                'user' => [
                    'id' => $requestUser->getId(),
                    'username' => $requestUser->getUsername(),
                    'avatarName' => $requestUser->getAvatarName(),
                    'level' => $requestUser->getLevel()?->getName() ?? '1',
                    'famePoints' => $requestUser->getFamePoints(),
                ],
                'createdAt' => $request->getCreatedAt()->format('c'),
                'isRead' => $request->isRead(),
                'approved' => $request->isApproved(),
                'status' => $request->isApproved() === null ? 'pending' : ($request->isApproved() ? 'approved' : 'rejected'),
            ];
        }

        return $requestsData;
    }

    public function approveJoinRequest(Ship $ship, User $owner, int $requestId): ShipMember
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $this->entityManager->lock($owner, LockMode::PESSIMISTIC_WRITE);
            $this->entityManager->lock($ship, LockMode::PESSIMISTIC_WRITE);

            $member = $this->shipMemberRepository->findOneBy(['user' => $owner, 'ship' => $ship]);
            if (!$member || !$member->isOwner()) {
                throw new OperationForbiddenException('shipOwnerRequired');
            }

            $request = $this->shipJoinRequestRepository->find($requestId);
            if (!$request) {
                throw new ResourceNotFoundException('shipJoinRequestNotFound');
            }

            if ($request->getShip()->getId() !== $ship->getId()) {
                throw new OperationForbiddenException('shipJoinRequestNotForShip');
            }

            if ($request->isApproved() !== null) {
                throw new BusinessRuleException('shipJoinRequestAlreadyHandled');
            }

            $joinUser = $request->getUser();

            $existingMember = $this->shipMemberRepository->findOneBy(['user' => $joinUser]);
            if ($existingMember) {
                throw new BusinessRuleException('alreadyInShip');
            }

            $currentMembersCount = $this->shipMemberRepository->count(['ship' => $ship]);
            if ($currentMembersCount >= $ship->getMaxMembers()) {
                throw new BusinessRuleException('shipMaxMembersReached');
            }

            $newMember = $this->shipMembershipMutationHelper->addMember($ship, $joinUser);

            $request->setApproved(true);

            $pendingInvitation = $this->shipInvitationRepository->findOneBy([
                'user' => $joinUser,
                'ship' => $ship,
                'accepted' => null,
            ]);
            if ($pendingInvitation) {
                $pendingInvitation->setAccepted(true);
                $this->entityManager->persist($pendingInvitation);
            }

            $this->entityManager->persist($request);
            $this->entityManager->flush();

            $connection->commit();

            return $newMember;
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function rejectJoinRequest(Ship $ship, User $owner, int $requestId): void
    {
        $member = $this->shipMemberRepository->findOneBy(['user' => $owner, 'ship' => $ship]);
        if (!$member || !$member->isOwner()) {
            throw new OperationForbiddenException('shipOwnerRequired');
        }

        $request = $this->shipJoinRequestRepository->find($requestId);
        if (!$request) {
            throw new ResourceNotFoundException('shipJoinRequestNotFound');
        }

        if ($request->getShip()->getId() !== $ship->getId()) {
            throw new OperationForbiddenException('shipJoinRequestNotForShip');
        }

        if ($request->isApproved() !== null) {
            throw new BusinessRuleException('shipJoinRequestAlreadyHandled');
        }

        $request->setApproved(false);
        $this->entityManager->persist($request);
        $this->entityManager->flush();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildJoinRequestSentResponse(ShipJoinRequest $joinRequest): array
    {
        return [
            'request' => [
                'id' => $joinRequest->getId(),
                'createdAt' => $joinRequest->getCreatedAt()->format('c'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildJoinShipResponse(ShipMember $member): array
    {
        return [
            'member' => [
                'id' => $member->getId(),
                'role' => $member->getRole()?->value,
                'joinedAt' => $member->getJoinedAt()->format('c'),
            ],
        ];
    }
}
