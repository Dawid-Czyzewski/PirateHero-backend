<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Ship;

use App\Entity\Level;
use App\Entity\Ship;
use App\Entity\ShipInvitation;
use App\Entity\ShipMember;
use App\Entity\User;
use App\Exception\OperationForbiddenException;
use App\Exception\ResourceNotFoundException;
use App\Repository\ShipInvitationRepository;
use App\Repository\ShipJoinRequestRepository;
use App\Repository\ShipMemberRepository;
use App\Repository\UserRepository;
use App\Service\Ship\ShipInvitationService;
use App\Service\Ship\ShipMembershipMutationHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ShipInvitationServiceTest extends TestCase
{
    public function testInviteMemberThrowsWhenOwnerMissing(): void
    {
        $ship = new Ship();
        $inviter = $this->makeUser();

        $memberRepo = $this->createMock(ShipMemberRepository::class);
        $memberRepo->method('findOneBy')->willReturn(null);

        $service = $this->makeService(shipMemberRepository: $memberRepo);

        $this->expectException(OperationForbiddenException::class);
        $this->expectExceptionMessage('shipOwnerRequired');
        $service->inviteMember($ship, $inviter, 'someone');
    }

    public function testInviteMemberThrowsWhenUserUnknown(): void
    {
        $ship = new Ship();
        $inviter = $this->makeUser();

        $ownerMember = $this->createMock(ShipMember::class);
        $ownerMember->method('isOwner')->willReturn(true);

        $memberRepo = $this->createMock(ShipMemberRepository::class);
        $memberRepo->expects(self::once())->method('findOneBy')->willReturn($ownerMember);
        $memberRepo->method('count')->with(['ship' => $ship])->willReturn(1);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findOneBy')->with(['username' => 'ghost'])->willReturn(null);

        $service = $this->makeService(shipMemberRepository: $memberRepo, userRepository: $userRepo);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('userNotFound');
        $service->inviteMember($ship, $inviter, 'ghost');
    }

    public function testGetInvitationForUserUsesRepositoryFilters(): void
    {
        $ship = new Ship();
        $user = $this->makeUser();
        $invitation = new ShipInvitation();

        $repo = $this->createMock(ShipInvitationRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with([
                'user' => $user,
                'ship' => $ship,
                'accepted' => null,
            ])
            ->willReturn($invitation);

        $service = $this->makeService(shipInvitationRepository: $repo);
        self::assertSame($invitation, $service->getInvitationForUser($ship, $user));
    }

    private function makeService(
        ?EntityManagerInterface $entityManager = null,
        ?ShipMemberRepository $shipMemberRepository = null,
        ?ShipJoinRequestRepository $shipJoinRequestRepository = null,
        ?ShipInvitationRepository $shipInvitationRepository = null,
        ?UserRepository $userRepository = null,
        ?ShipMembershipMutationHelper $shipMembershipMutationHelper = null,
    ): ShipInvitationService {
        return new ShipInvitationService(
            $entityManager ?? $this->createMock(EntityManagerInterface::class),
            $shipMemberRepository ?? $this->createMock(ShipMemberRepository::class),
            $shipJoinRequestRepository ?? $this->createMock(ShipJoinRequestRepository::class),
            $shipInvitationRepository ?? $this->createMock(ShipInvitationRepository::class),
            $userRepository ?? $this->createMock(UserRepository::class),
            $shipMembershipMutationHelper ?? $this->createMock(ShipMembershipMutationHelper::class),
        );
    }

    private function makeUser(): User
    {
        $level = (new Level())->setName('1')->setExpToNextLevel(220);

        return (new User())
            ->setEmail(sprintf('invite_%s@test.local', bin2hex(random_bytes(4))))
            ->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))))
            ->setPassword('hash')
            ->setLevel($level)
            ->setGold(100)
            ->setdiamonds(10)
            ->setEnergyPoints(100)
            ->setTrainingPoints(10)
            ->setDuelPoints(10)
            ->setFamePoints(10);
    }
}
