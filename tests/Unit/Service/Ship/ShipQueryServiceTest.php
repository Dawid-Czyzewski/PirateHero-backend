<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Ship;

use App\Entity\Level;
use App\Entity\Ship;
use App\Entity\ShipInvitation;
use App\Entity\User;
use App\Repository\ShipFightNotificationRepository;
use App\Repository\ShipInvitationRepository;
use App\Repository\ShipJoinRequestRepository;
use App\Repository\ShipMemberRepository;
use App\Repository\ShipRemovalNotificationRepository;
use App\Repository\ShipRepository;
use App\Repository\ShipUpgradeLevelCostRepository;
use App\Service\Ship\ShipChatService;
use App\Service\Ship\ShipMembershipService;
use App\Service\Ship\ShipQueryService;
use App\Service\Ship\ShipUpgradePricingService;
use PHPUnit\Framework\TestCase;

final class ShipQueryServiceTest extends TestCase
{
    private function pricingService(): ShipUpgradePricingService
    {
        $repo = $this->createMock(ShipUpgradeLevelCostRepository::class);
        $repo->method('findBy')->willReturn([]);

        return new ShipUpgradePricingService($repo);
    }

    public function testGetMyShipDataReturnsNullWhenUserHasNoShip(): void
    {
        $membership = $this->createMock(ShipMembershipService::class);
        $membership->method('getShipForUser')->willReturn(null);

        $service = new ShipQueryService(
            $membership,
            $this->createMock(ShipChatService::class),
            $this->createMock(ShipMemberRepository::class),
            $this->createMock(ShipFightNotificationRepository::class),
            $this->createMock(ShipInvitationRepository::class),
            $this->createMock(ShipJoinRequestRepository::class),
            $this->createMock(ShipRemovalNotificationRepository::class),
            $this->pricingService(),
            $this->createMock(ShipRepository::class),
        );

        $user = $this->createMock(User::class);
        self::assertNull($service->getMyShipData($user));
    }

    public function testGetUnreadNotificationsCountAggregatesRepositories(): void
    {
        $membership = $this->createMock(ShipMembershipService::class);
        $membership->method('getShipForUser')->willReturn(null);
        $membership->method('getShipMember')->willReturn(null);

        $invitationRepo = $this->createMock(ShipInvitationRepository::class);
        $invitationRepo->method('count')->willReturn(2);

        $removalRepo = $this->createMock(ShipRemovalNotificationRepository::class);
        $removalRepo->method('count')->willReturn(3);

        $service = new ShipQueryService(
            $membership,
            $this->createMock(ShipChatService::class),
            $this->createMock(ShipMemberRepository::class),
            $this->createMock(ShipFightNotificationRepository::class),
            $invitationRepo,
            $this->createMock(ShipJoinRequestRepository::class),
            $removalRepo,
            $this->pricingService(),
            $this->createMock(ShipRepository::class),
        );

        self::assertSame(5, $service->getUnreadNotificationsCount($this->createMock(User::class)));
    }

    public function testGetShipPreviewDataBuildsBasicShape(): void
    {
        $ship = (new Ship())
            ->setTitle('Test Ship')
            ->setDescription('Desc')
            ->setMaxMembers(20)
            ->setRequiresInvitation(false);
        $ref = new \ReflectionClass($ship);
        $idProp = $ref->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($ship, 42);

        $memberRepo = $this->createMock(ShipMemberRepository::class);
        $memberRepo->method('findBy')->willReturn([]);

        $joinRepo = $this->createMock(ShipJoinRequestRepository::class);
        $joinRepo->method('findOneBy')->willReturn(null);

        $membership = $this->createMock(ShipMembershipService::class);
        $membership->method('getShipMember')->willReturn(null);

        $service = new ShipQueryService(
            $membership,
            $this->createMock(ShipChatService::class),
            $memberRepo,
            $this->createMock(ShipFightNotificationRepository::class),
            $this->createMock(ShipInvitationRepository::class),
            $joinRepo,
            $this->createMock(ShipRemovalNotificationRepository::class),
            $this->pricingService(),
            $this->createMock(ShipRepository::class),
        );

        $preview = $service->getShipPreviewData($ship, null);
        self::assertSame(42, $preview['id']);
        self::assertSame('Test Ship', $preview['title']);
        self::assertSame(0, $preview['membersCount']);
        self::assertFalse($preview['isFull']);
    }

    public function testGetInvitationsDataIncludesInviterLevelAndFame(): void
    {
        $ship = $this->createMock(Ship::class);
        $ship->method('getId')->willReturn(3);
        $ship->method('getTitle')->willReturn('Test Ship');

        $level = $this->createMock(Level::class);
        $level->method('getName')->willReturn('17');

        $inviter = $this->createMock(User::class);
        $inviter->method('getId')->willReturn('99');
        $inviter->method('getUsername')->willReturn('Captain');
        $inviter->method('getLevel')->willReturn($level);
        $inviter->method('getFamePoints')->willReturn(500);

        $invitation = $this->createMock(ShipInvitation::class);
        $invitation->method('getShip')->willReturn($ship);
        $invitation->method('getInviter')->willReturn($inviter);
        $invitation->method('getId')->willReturn(42);
        $invitation->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2026-03-15T12:00:00+00:00'));
        $invitation->method('isRead')->willReturn(false);
        $invitation->method('isAccepted')->willReturn(null);

        $invitationRepo = $this->createMock(ShipInvitationRepository::class);
        $invitationRepo->method('findBy')->willReturn([$invitation]);

        $membership = $this->createMock(ShipMembershipService::class);

        $service = new ShipQueryService(
            $membership,
            $this->createMock(ShipChatService::class),
            $this->createMock(ShipMemberRepository::class),
            $this->createMock(ShipFightNotificationRepository::class),
            $invitationRepo,
            $this->createMock(ShipJoinRequestRepository::class),
            $this->createMock(ShipRemovalNotificationRepository::class),
            $this->pricingService(),
            $this->createMock(ShipRepository::class),
        );

        $viewer = $this->createMock(User::class);
        $out = $service->getInvitationsData($viewer);
        self::assertCount(1, $out);
        self::assertSame(42, $out[0]['id']);
        self::assertSame('17', $out[0]['inviter']['level']);
        self::assertSame(500, $out[0]['inviter']['famePoints']);
        self::assertSame('Captain', $out[0]['inviter']['username']);
    }
}
