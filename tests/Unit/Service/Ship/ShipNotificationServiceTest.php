<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Ship;

use App\Entity\User;
use App\Exception\ResourceNotFoundException;
use App\Repository\ShipFightNotificationRepository;
use App\Repository\ShipInvitationRepository;
use App\Repository\ShipJoinRequestRepository;
use App\Repository\ShipRemovalNotificationRepository;
use App\Service\Ship\ShipMembershipService;
use App\Service\Ship\ShipNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ShipNotificationServiceTest extends TestCase
{
    public function testMarkRemovalNotificationThrowsWhenMissing(): void
    {
        $removalRepo = $this->createMock(ShipRemovalNotificationRepository::class);
        $removalRepo->method('find')->with(55)->willReturn(null);

        $service = $this->createService(
            removalRepo: $removalRepo,
        );

        $user = $this->createConfiguredMock(User::class, ['getId' => 'user-1']);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('removalNotificationNotFound');
        $service->markRemovalNotificationAsRead($user, 55);
    }

    public function testMarkFightNotificationThrowsWhenMissing(): void
    {
        $fightRepo = $this->createMock(ShipFightNotificationRepository::class);
        $fightRepo->method('find')->with(66)->willReturn(null);

        $service = $this->createService(
            fightRepo: $fightRepo,
        );

        $user = $this->createConfiguredMock(User::class, ['getId' => 'user-1']);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('fightNotificationNotFound');
        $service->markFightNotificationAsRead($user, 66);
    }

    private function createService(
        ?ShipRemovalNotificationRepository $removalRepo = null,
        ?ShipFightNotificationRepository $fightRepo = null,
    ): ShipNotificationService {
        return new ShipNotificationService(
            $removalRepo ?? $this->createMock(ShipRemovalNotificationRepository::class),
            $fightRepo ?? $this->createMock(ShipFightNotificationRepository::class),
            $this->createMock(ShipInvitationRepository::class),
            $this->createMock(ShipJoinRequestRepository::class),
            $this->createMock(ShipMembershipService::class),
            $this->createMock(EntityManagerInterface::class),
        );
    }
}
