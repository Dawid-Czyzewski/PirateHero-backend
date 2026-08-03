<?php

declare(strict_types=1);

namespace App\Service\Ship;

use App\Entity\Ship;
use App\Entity\ShipFightNotification;
use App\Entity\ShipMember;
use App\Entity\ShipMessage;
use App\Entity\ShipRemovalNotification;
use App\Entity\User;
use App\Exception\ResourceNotFoundException;
use App\Mapper\Api\ShipMapper;
use App\Repository\ShipFightNotificationRepository;
use App\Repository\ShipInvitationRepository;
use App\Repository\ShipJoinRequestRepository;
use App\Repository\ShipMemberRepository;
use App\Repository\ShipRemovalNotificationRepository;
use App\Repository\ShipRepository;

readonly class ShipQueryService
{
    public function __construct(
        private ShipMembershipService $shipMembershipService,
        private ShipChatService $shipChatService,
        private ShipMemberRepository $shipMemberRepository,
        private ShipFightNotificationRepository $shipFightNotificationRepository,
        private ShipInvitationRepository $shipInvitationRepository,
        private ShipJoinRequestRepository $shipJoinRequestRepository,
        private ShipRemovalNotificationRepository $shipRemovalNotificationRepository,
        private ShipUpgradePricingService $shipUpgradePricingService,
        private ShipRepository $shipRepository,
    ) {
    }

    public function getMyShipData(User $user): ?array
    {
        $ship = $this->shipMembershipService->getShipForUser($user);
        if (!$ship) {
            return null;
        }

        $member = $this->shipMembershipService->getShipMember($user);
        $members = $this->getShipMembers($ship);
        $messages = $this->shipChatService->getMessages($ship, 50);

        $membersData = [];
        foreach ($members as $m) {
            $membersData[] = [
                'id' => $m->getId(),
                'role' => $m->getRole()?->value,
                'joinedAt' => $m->getJoinedAt()->format('c'),
                'goldContributed' => $m->getGoldContributed(),
                'diamondsContributed' => $m->getDiamondsContributed(),
                'user' => [
                    'id' => $m->getUser()->getId(),
                    'username' => $m->getUser()->getUsername(),
                    'avatarName' => $m->getUser()->getAvatarName(),
                    'level' => $m->getUser()->getLevel()?->getName() ?? '1',
                    'levelId' => $m->getUser()->getLevel()?->getId() ?? 0,
                    'famePoints' => $m->getUser()->getFamePoints(),
                ],
            ];
        }

        $messagesData = [];
        foreach ($messages as $msg) {
            $messageData = [
                'id' => $msg->getId(),
                'content' => $msg->getContent(),
                'createdAt' => $msg->getCreatedAt()->format('c'),
                'isSystem' => $msg->isSystem(),
            ];

            if ($msg->getAuthor() !== null) {
                $messageData['author'] = [
                    'id' => $msg->getAuthor()->getId(),
                    'username' => $msg->getAuthor()->getUsername(),
                ];
            }

            $messagesData[] = $messageData;
        }

        $shipUpgradePricing = $this->shipUpgradePricingService->getPricingMatrixForApi();

        return ShipMapper::myShipData([
            'shipUpgradePricing' => $shipUpgradePricing,
            'ship' => [
                'id' => $ship->getId(),
                'title' => $ship->getTitle(),
                'description' => $ship->getDescription(),
                'internalNotes' => $ship->getInternalNotes(),
                'createdAt' => $ship->getCreatedAt()->format('c'),
                'gold' => $ship->getGold(),
                'diamonds' => $ship->getDiamonds(),
                'skillsUpgrade' => $ship->getSkillsUpgrade(),
                'workUpgrade' => $ship->getWorkUpgrade(),
                'missionsUpgrade' => $ship->getMissionsUpgrade(),
                'hullUpgrade' => $ship->getHullUpgrade(),
                'maxMembers' => $ship->getMaxMembers(),
                'requiresInvitation' => $ship->getRequiresInvitation(),
                'famePoints' => $ship->getFamePoints(),
            ],
            'member' => $member ? [
                'id' => $member->getId(),
                'role' => $member->getRole()?->value,
                'joinedAt' => $member->getJoinedAt()->format('c'),
                'goldContributed' => $member->getGoldContributed(),
                'diamondsContributed' => $member->getDiamondsContributed(),
            ] : null,
            'members' => $membersData,
            'messages' => $messagesData,
        ])->toArray();
    }

    public function getInvitationsData(User $user): array
    {
        $invitations = $this->shipInvitationRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        $out = [];
        foreach ($invitations as $invitation) {
            $ship = $invitation->getShip();
            $inviter = $invitation->getInviter();
            $out[] = [
                'id' => $invitation->getId(),
                'ship' => ['id' => $ship->getId(), 'title' => $ship->getTitle()],
                'inviter' => [
                    'id' => $inviter->getId(),
                    'username' => $inviter->getUsername(),
                    'level' => $inviter->getLevel()?->getName() ?? '1',
                    'famePoints' => $inviter->getFamePoints(),
                ],
                'createdAt' => $invitation->getCreatedAt()->format('c'),
                'isRead' => $invitation->isRead(),
                'accepted' => $invitation->isAccepted(),
                'status' => $invitation->isAccepted() === null ? 'pending' : ($invitation->isAccepted() ? 'accepted' : 'declined'),
            ];
        }

        return $out;
    }

    public function requireShipById(int $id): Ship
    {
        $ship = $this->shipRepository->find($id);
        if (!$ship) {
            throw new ResourceNotFoundException('shipNotFound');
        }

        return $ship;
    }

    /**
     * @return array<string, mixed>
     */
    public function getShipPreviewById(int $id, User $user): array
    {
        return $this->getShipPreviewData($this->requireShipById($id), $user);
    }

    /**
     * @return array<string, mixed>
     */
    public function toShipSummaryArray(Ship $ship): array
    {
        return [
            'id' => $ship->getId(),
            'title' => $ship->getTitle(),
            'description' => $ship->getDescription(),
            'internalNotes' => $ship->getInternalNotes(),
            'createdAt' => $ship->getCreatedAt()->format('c'),
            'maxMembers' => $ship->getMaxMembers(),
            'hullUpgrade' => $ship->getHullUpgrade(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildUpdateResponse(Ship $ship, ?ShipMessage $systemMessage): array
    {
        $data = ['ship' => $this->toShipSummaryArray($ship)];

        if ($systemMessage instanceof ShipMessage) {
            $data['shipMessage'] = $this->systemMessageWithTreasury($ship, $systemMessage);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function systemMessageWithTreasury(Ship $ship, ShipMessage $message): array
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

    public function getUnreadNotificationsCount(User $user): int
    {
        $unreadInvitations = $this->shipInvitationRepository->count([
            'user' => $user,
            'accepted' => null,
            'isRead' => false,
        ]);

        $unreadRemovalNotifications = $this->shipRemovalNotificationRepository->count([
            'user' => $user,
            'isRead' => false,
        ]);

        $unreadFightNotifications = $this->shipFightNotificationRepository->count([
            'user' => $user,
            'isRead' => false,
        ]);

        $ship = $this->shipMembershipService->getShipForUser($user);
        $unreadJoinRequests = 0;
        if ($ship) {
            $member = $this->shipMembershipService->getShipMember($user);
            if ($member && $member->isOwner()) {
                $unreadJoinRequests = $this->shipJoinRequestRepository->count([
                    'ship' => $ship,
                    'approved' => null,
                    'isRead' => false,
                ]);
            }
        }

        return $unreadInvitations + $unreadJoinRequests + $unreadRemovalNotifications + $unreadFightNotifications;
    }

    public function getShipPreviewData(Ship $ship, ?User $user = null): array
    {
        $members = $this->getShipMembers($ship);

        $membersData = [];
        foreach ($members as $m) {
            $membersData[] = [
                'id' => $m->getId(),
                'role' => $m->getRole()?->value,
                'joinedAt' => $m->getJoinedAt()->format('c'),
                'goldContributed' => $m->getGoldContributed(),
                'diamondsContributed' => $m->getDiamondsContributed(),
                'user' => [
                    'id' => $m->getUser()->getId(),
                    'username' => $m->getUser()->getUsername(),
                    'avatarName' => $m->getUser()->getAvatarName(),
                    'level' => $m->getUser()->getLevel()?->getName() ?? '1',
                    'levelId' => $m->getUser()->getLevel()?->getId() ?? 0,
                ],
            ];
        }

        $hasPendingRequest = false;
        $isOwner = false;
        if ($user) {
            $pendingRequest = $this->shipJoinRequestRepository->findOneBy([
                'user' => $user,
                'ship' => $ship,
                'approved' => null,
            ]);
            $hasPendingRequest = $pendingRequest !== null;

            $member = $this->shipMembershipService->getShipMember($user);
            if ($member && $member->getShip()->getId() === $ship->getId() && $member->isOwner()) {
                $isOwner = true;
            }
        }

        $membersCount = count($membersData);
        $isFull = $membersCount >= $ship->getMaxMembers();

        return ShipMapper::preview([
            'id' => $ship->getId(),
            'title' => $ship->getTitle(),
            'description' => $ship->getDescription(),
            'createdAt' => $ship->getCreatedAt()->format('c'),
            'skillsUpgrade' => $ship->getSkillsUpgrade(),
            'workUpgrade' => $ship->getWorkUpgrade(),
            'missionsUpgrade' => $ship->getMissionsUpgrade(),
            'hullUpgrade' => $ship->getHullUpgrade(),
            'maxMembers' => $ship->getMaxMembers(),
            'requiresInvitation' => $ship->getRequiresInvitation(),
            'famePoints' => $ship->getFamePoints(),
            'members' => $membersData,
            'membersCount' => $membersCount,
            'hasPendingRequest' => $hasPendingRequest,
            'isOwner' => $isOwner,
            'isFull' => $isFull,
        ])->toArray();
    }

    public function getRemovalNotifications(User $user): array
    {
        $notifications = $this->shipRemovalNotificationRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return array_map(static function (ShipRemovalNotification $notification): array {
            $ship = $notification->getShip();
            $remover = $notification->getRemover();

            return [
                'id' => $notification->getId(),
                'ship' => [
                    'id' => $ship->getId(),
                    'title' => $ship->getTitle(),
                ],
                'remover' => [
                    'id' => $remover->getId(),
                    'username' => $remover->getUsername(),
                ],
                'createdAt' => $notification->getCreatedAt()->format('c'),
                'isRead' => $notification->isRead(),
            ];
        }, $notifications);
    }

    public function getFightNotifications(User $user): array
    {
        $notifications = $this->shipFightNotificationRepository->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return array_map(static function (ShipFightNotification $notification): array {
            $attackerShip = $notification->getAttackerShip();
            $defenderShip = $notification->getDefenderShip();

            return [
                'id' => $notification->getId(),
                'attackerShip' => [
                    'id' => $attackerShip->getId(),
                    'title' => $attackerShip->getTitle(),
                ],
                'defenderShip' => [
                    'id' => $defenderShip->getId(),
                    'title' => $defenderShip->getTitle(),
                ],
                'fightType' => $notification->getFightType(),
                'createdAt' => $notification->getCreatedAt()->format('c'),
                'isRead' => $notification->isRead(),
            ];
        }, $notifications);
    }

    private function getShipMembers(Ship $ship): array
    {
        $members = $this->shipMemberRepository->findBy(['ship' => $ship], ['joinedAt' => 'ASC']);

        $rolePriority = [
            'OWNER' => 0,
            'MANAGER' => 1,
            'MEMBER' => 2,
        ];

        usort($members, static function (ShipMember $a, ShipMember $b) use ($rolePriority): int {
            $roleA = $a->getRole()?->value;
            $roleB = $b->getRole()?->value;
            $weightA = $rolePriority[$roleA] ?? 999;
            $weightB = $rolePriority[$roleB] ?? 999;

            if ($weightA !== $weightB) {
                return $weightA <=> $weightB;
            }

            $levelIdA = $a->getUser()?->getLevel()?->getId() ?? 0;
            $levelIdB = $b->getUser()?->getLevel()?->getId() ?? 0;
            if ($levelIdA !== $levelIdB) {
                return $levelIdB <=> $levelIdA;
            }

            $joinedA = $a->getJoinedAt()?->getTimestamp() ?? 0;
            $joinedB = $b->getJoinedAt()?->getTimestamp() ?? 0;

            return $joinedA <=> $joinedB;
        });

        return $members;
    }
}
