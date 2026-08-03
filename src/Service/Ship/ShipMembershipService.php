<?php

declare(strict_types=1);

namespace App\Service\Ship;

use App\Domain\Constants\ShipConstants;
use App\Entity\Ship;
use App\Entity\ShipMember;
use App\Entity\ShipMessage;
use App\Entity\ShipRemovalNotification;
use App\Entity\User;
use App\Enum\ShipRole;
use App\Exception\BusinessRuleException;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Repository\ShipMemberRepository;
use App\Repository\ShipMessageRepository;
use App\Repository\UserRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

class ShipMembershipService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ShipMemberRepository $shipMemberRepository,
        private ShipMessageRepository $shipMessageRepository,
        private UserRepository $userRepository,
        private ShipChatService $shipChatService,
    ) {
    }

    public function createShip(User $user, string $title, ?string $description = null): Ship
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $managedUser = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if ($managedUser === null) {
                throw new ResourceNotFoundException('userNotFound');
            }

            $existingMember = $this->shipMemberRepository->findOneBy(['user' => $managedUser]);
            if ($existingMember) {
                throw new BusinessRuleException('alreadyInShip');
            }

            $managedUser->spendGold(ShipConstants::CLUB_CREATION_COST, 'notEnoughGoldForShipCreation');

            $ship = new Ship();
            $ship->setTitle($title);
            $ship->setDescription($description);
            $ship->setMaxMembers(ShipConstants::BASE_CREW_SLOTS);
            $ship->setHullUpgrade(0);

            $member = new ShipMember();
            $member->setUser($managedUser);
            $member->setRole(ShipRole::OWNER);
            $ship->addMember($member);
            $managedUser->addShipMember($member);

            $this->entityManager->persist($ship);
            $this->entityManager->flush();
            $connection->commit();

            return $ship;
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function getShipForUser(User $user): ?Ship
    {
        $member = $this->shipMemberRepository->findOneBy(['user' => $user]);

        return $member ? $member->getShip() : null;
    }

    public function getUserByIdentifier(string $identifier): ?User
    {
        $byEmail = $this->userRepository->findOneBy(['email' => $identifier]);
        if ($byEmail !== null) {
            return $byEmail;
        }

        return $this->userRepository->findOneBy(['username' => $identifier]);
    }

    public function getShipMember(User $user): ?ShipMember
    {
        return $this->shipMemberRepository->findOneBy(['user' => $user]);
    }

    public function requireShipMember(User $user): ShipMember
    {
        $shipMember = $this->getShipMember($user);
        if ($shipMember === null || $shipMember->getShip() === null) {
            throw new OperationForbiddenException('shipMembershipRequired');
        }

        return $shipMember;
    }

    public function requireShipOwner(User $user): ShipMember
    {
        $shipMember = $this->requireShipMember($user);
        if (!$shipMember->isOwner()) {
            throw new OperationForbiddenException('shipOwnerRequired');
        }

        return $shipMember;
    }

    public function requireOwnerShip(User $user): Ship
    {
        $ship = $this->requireShipOwner($user)->getShip();
        if ($ship === null) {
            throw new OperationForbiddenException('shipMembershipRequired');
        }

        return $ship;
    }

    public function requireShipForUser(User $user): Ship
    {
        $ship = $this->requireShipMember($user)->getShip();
        if ($ship === null) {
            throw new OperationForbiddenException('shipMembershipRequired');
        }

        return $ship;
    }

    public function isUserOwner(User $user, Ship $ship): bool
    {
        $member = $this->shipMemberRepository->findOneBy(['user' => $user, 'ship' => $ship]);

        return $member && $member->isOwner();
    }

    public function isUserMember(User $user, Ship $ship): bool
    {
        $member = $this->shipMemberRepository->findOneBy(['user' => $user, 'ship' => $ship]);

        return $member !== null;
    }

    public function setInvitationRequired(Ship $ship, User $user, bool $requiresInvitation): void
    {
        $member = $this->shipMemberRepository->findOneBy(['user' => $user, 'ship' => $ship]);
        if (!$member || !$member->isOwner()) {
            throw new OperationForbiddenException('shipOwnerRequired');
        }

        $ship->setRequiresInvitation($requiresInvitation);
        $this->entityManager->flush();
    }

    public function joinShip(Ship $ship, User $user): ShipMember
    {
        if ($ship->getRequiresInvitation()) {
            throw new BusinessRuleException('shipInvitationRequired');
        }

        $existingMember = $this->shipMemberRepository->findOneBy(['user' => $user]);
        if ($existingMember) {
            throw new BusinessRuleException('alreadyInShip');
        }

        $currentMembersCount = $this->shipMemberRepository->count(['ship' => $ship]);
        if ($currentMembersCount >= $ship->getMaxMembers()) {
            throw new BusinessRuleException('shipMaxMembersReached');
        }

        $member = new ShipMember();
        $member->setUser($user);
        $member->setRole(ShipRole::MEMBER);
        $ship->addMember($member);
        $user->addShipMember($member);

        $this->entityManager->persist($member);
        $this->entityManager->flush();

        $this->shipChatService->addSystemMessage($ship, 'shipPage.chatSystem.memberJoined', [
            'name' => $user->getUsername() ?? '',
        ]);

        return $member;
    }

    public function removeMember(Ship $ship, User $remover, User $userToRemove): ShipMessage
    {
        $removerMember = $this->shipMemberRepository->findOneBy(['user' => $remover, 'ship' => $ship]);
        if (!$removerMember) {
            throw new OperationForbiddenException('shipMembershipRequired');
        }

        if ($remover->getId() === $userToRemove->getId()) {
            throw new BusinessRuleException('cannotRemoveSelf');
        }

        $memberToRemove = $this->shipMemberRepository->findOneBy(['user' => $userToRemove, 'ship' => $ship]);
        if (!$memberToRemove) {
            throw new BusinessRuleException('targetUserNotShipMember');
        }

        if ($memberToRemove->isOwner()) {
            throw new BusinessRuleException('cannotRemoveShipOwner');
        }

        if ($removerMember->isManager() && !$memberToRemove->isMember()) {
            throw new OperationForbiddenException('shipManagerCanRemoveMemberOnly');
        }

        if (!$removerMember->isOwner() && !$removerMember->isManager()) {
            throw new OperationForbiddenException('shipManagerOrOwnerRequired');
        }

        $notification = new ShipRemovalNotification();
        $notification->setUser($userToRemove);
        $notification->setShip($ship);
        $notification->setRemover($remover);

        $this->entityManager->persist($notification);
        $this->entityManager->remove($memberToRemove);
        $this->entityManager->flush();

        return $this->shipChatService->addSystemMessage($ship, 'shipPage.chatSystem.memberKicked', [
            'target' => $userToRemove->getUsername() ?? '',
            'by' => $remover->getUsername() ?? '',
        ]);
    }

    public function leaveShip(Ship $ship, User $user): void
    {
        $member = $this->shipMemberRepository->findOneBy(['user' => $user, 'ship' => $ship]);
        if (!$member) {
            throw new OperationForbiddenException('shipMembershipRequired');
        }

        $username = $user->getUsername();

        if ($member->isOwner()) {
            $members = $this->shipMemberRepository->findBy(['ship' => $ship]);
            foreach ($members as $m) {
                $this->entityManager->remove($m);
            }
            $this->entityManager->remove($ship);
        } else {
            $this->entityManager->remove($member);
            $this->shipChatService->addSystemMessage($ship, 'shipPage.chatSystem.memberLeft', ['name' => $username ?? '']);
        }

        $this->entityManager->flush();
    }

    public function transferOwnership(Ship $ship, User $currentOwner, User $newOwner): void
    {
        $ownerMember = $this->shipMemberRepository->findOneBy(['user' => $currentOwner, 'ship' => $ship]);
        if (!$ownerMember || !$ownerMember->isOwner()) {
            throw new OperationForbiddenException('shipOwnerRequired');
        }

        $newOwnerMember = $this->shipMemberRepository->findOneBy(['user' => $newOwner, 'ship' => $ship]);
        if (!$newOwnerMember) {
            throw new BusinessRuleException('newOwnerMustBeShipMember');
        }

        $ownerMember->setRole(ShipRole::MEMBER);
        $newOwnerMember->setRole(ShipRole::OWNER);

        $this->entityManager->flush();
    }

    public function changeMemberRole(Ship $ship, User $changer, User $targetUser, ShipRole $newRole): void
    {
        $changerMember = $this->shipMemberRepository->findOneBy(['user' => $changer, 'ship' => $ship]);
        if (!$changerMember || !$changerMember->isOwner()) {
            throw new OperationForbiddenException('shipOwnerRequired');
        }

        $targetMember = $this->shipMemberRepository->findOneBy(['user' => $targetUser, 'ship' => $ship]);
        if (!$targetMember) {
            throw new BusinessRuleException('targetUserNotShipMember');
        }

        if ($changer->getId() === $targetUser->getId()) {
            throw new BusinessRuleException('cannotChangeOwnRole');
        }

        if ($targetMember->isOwner()) {
            throw new BusinessRuleException('cannotChangeOwnerRole');
        }

        if ($newRole === ShipRole::OWNER) {
            $changerMember->setRole(ShipRole::MANAGER);
        }

        $targetMember->setRole($newRole);
        $this->entityManager->flush();
    }

    public function searchUsers(string $username, int $limit = 10): array
    {
        return $this->userRepository->createQueryBuilder('u')
            ->where('u.username LIKE :username')
            ->andWhere('u.activateToken IS NULL')
            ->setParameter('username', '%'.$username.'%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function updateShip(Ship $ship, User $editor, ?string $title = null, ?string $description = null, ?string $internalNotes = null): ?ShipMessage
    {
        $previousInternalNotes = $ship->getInternalNotes();
        if ($title !== null) {
            $ship->setTitle($title);
        }
        if ($description !== null) {
            $ship->setDescription($description);
        }
        if ($internalNotes !== null) {
            $ship->setInternalNotes($internalNotes);
        }

        $this->entityManager->flush();

        if ($internalNotes !== null && (string) $previousInternalNotes !== (string) $internalNotes) {
            $message = $this->shipChatService->addSystemMessage(
                $ship,
                'shipPage.chatSystem.internalNotesUpdated',
                ['by' => $editor->getUsername() ?? '']
            );
            $this->entityManager->refresh($ship);

            return $message;
        }

        $this->entityManager->refresh($ship);

        return null;
    }

    public function deleteShip(Ship $ship, User $user): void
    {
        $member = $this->shipMemberRepository->findOneBy(['user' => $user, 'ship' => $ship]);
        if (!$member || !$member->isOwner()) {
            throw new OperationForbiddenException('shipOwnerRequired');
        }

        $members = $this->shipMemberRepository->findBy(['ship' => $ship]);
        foreach ($members as $m) {
            $this->entityManager->remove($m);
        }

        $messages = $this->shipMessageRepository->findBy(['ship' => $ship]);
        foreach ($messages as $msg) {
            $this->entityManager->remove($msg);
        }

        $this->entityManager->remove($ship);
        $this->entityManager->flush();
    }

    public function requireUserById(?string $userId): User
    {
        if ($userId === null || $userId === '') {
            throw new ResourceNotFoundException('userNotFound');
        }

        $user = $this->userRepository->find($userId);
        if (!$user) {
            throw new ResourceNotFoundException('userNotFound');
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildRemoveMemberResponse(Ship $ship, ShipMessage $systemMessage): array
    {
        $this->entityManager->refresh($ship);

        return [
            'removed' => true,
            'shipMessage' => $this->treasurySystemMessageArray($ship, $systemMessage),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildMemberRoleChangedResponse(User $targetUser): array
    {
        $targetMember = $this->getShipMember($targetUser);
        if ($targetMember === null) {
            throw new ResourceNotFoundException('userNotFound');
        }

        return [
            'member' => [
                'id' => $targetMember->getId(),
                'role' => $targetMember->getRole()?->value,
                'user' => [
                    'id' => $targetMember->getUser()->getId(),
                    'username' => $targetMember->getUser()->getUsername(),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function searchUsersForApi(string $username): array
    {
        if ('' === $username) {
            return ['users' => []];
        }

        $users = $this->searchUsers($username, 10);
        $usersData = [];
        foreach ($users as $u) {
            $usersData[] = [
                'id' => $u->getId(),
                'username' => $u->getUsername(),
                'level' => $u->getLevel()?->getName() ?? '1',
                'famePoints' => $u->getFamePoints(),
            ];
        }

        return ['users' => $usersData];
    }

    /**
     * @return array<string, mixed>
     */
    private function treasurySystemMessageArray(Ship $ship, ShipMessage $message): array
    {
        return [
            'id' => $message->getId(),
            'content' => $message->getContent() ?? '',
            'createdAt' => $message->getCreatedAt()?->format(\DATE_ATOM) ?? (new \DateTimeImmutable())->format(\DATE_ATOM),
            'isSystem' => $message->isSystem(),
            'shipTreasury' => [
                'gold' => $ship->getGold(),
                'diamonds' => $ship->getDiamonds(),
            ],
        ];
    }
}
