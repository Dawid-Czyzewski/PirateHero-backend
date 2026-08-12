<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Ship;

use App\Entity\Level;
use App\Entity\Ship;
use App\Entity\ShipMember;
use App\Entity\ShipMessage;
use App\Entity\User;
use App\Enum\ShipRole;
use App\Exception\BusinessRuleException;
use App\Repository\ShipMemberRepository;
use App\Repository\ShipMessageRepository;
use App\Repository\UserRepository;
use App\Service\Progression\DailyChallengeService;
use App\Service\Ship\ShipChatService;
use App\Service\Ship\ShipMembershipService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ShipMembershipServiceTest extends TestCase
{
    public function testCreateShipThrowsWhenAlreadyMember(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('rollBack');

        $user = $this->makeUser();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('find')
            ->with(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE)
            ->willReturn($user);

        $memberRepo = $this->createMock(ShipMemberRepository::class);
        $memberRepo->method('findOneBy')->with(['user' => $user])->willReturn(new ShipMember());

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('find')->willReturn($user);

        $service = new ShipMembershipService(
            $em,
            $memberRepo,
            $this->createMock(ShipMessageRepository::class),
            $userRepo,
            $this->createMock(ShipChatService::class),
            $this->createMock(DailyChallengeService::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('alreadyInShip');
        $service->createShip($user, 'Guild');
    }

    public function testCreateShipThrowsWhenNotEnoughGold(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('rollBack');

        $user = $this->makeUser();
        $user->setGold(100);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('find')
            ->with(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE)
            ->willReturn($user);

        $memberRepo = $this->createMock(ShipMemberRepository::class);
        $memberRepo->method('findOneBy')->willReturn(null);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('find')->willReturn($user);

        $service = new ShipMembershipService(
            $em,
            $memberRepo,
            $this->createMock(ShipMessageRepository::class),
            $userRepo,
            $this->createMock(ShipChatService::class),
            $this->createMock(DailyChallengeService::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('notEnoughGoldForShipCreation');
        $service->createShip($user, 'Guild');
    }

    public function testJoinShipThrowsWhenInvitationRequired(): void
    {
        $ship = $this->createMock(Ship::class);
        $ship->method('getRequiresInvitation')->willReturn(true);

        $service = new ShipMembershipService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ShipMemberRepository::class),
            $this->createMock(ShipMessageRepository::class),
            $this->createMock(UserRepository::class),
            $this->createMock(ShipChatService::class),
            $this->createMock(DailyChallengeService::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('shipInvitationRequired');
        $service->joinShip($ship, $this->makeUser());
    }

    public function testGetShipForUserReturnsShipFromMembership(): void
    {
        $ship = (new Ship())->setTitle('Ship');
        $member = (new ShipMember())->setShip($ship)->setRole(ShipRole::OWNER)->setUser($this->makeUser());

        $shipMemberRepository = $this->createMock(ShipMemberRepository::class);
        $shipMemberRepository->method('findOneBy')->willReturn($member);

        $service = new ShipMembershipService(
            $this->createMock(EntityManagerInterface::class),
            $shipMemberRepository,
            $this->createMock(ShipMessageRepository::class),
            $this->createMock(UserRepository::class),
            $this->createMock(ShipChatService::class),
            $this->createMock(DailyChallengeService::class),
        );

        self::assertSame($ship, $service->getShipForUser($this->makeUser()));
    }

    public function testIsUserOwnerFalseWhenNoMembership(): void
    {
        $shipMemberRepository = $this->createMock(ShipMemberRepository::class);
        $shipMemberRepository->method('findOneBy')->willReturn(null);

        $service = new ShipMembershipService(
            $this->createMock(EntityManagerInterface::class),
            $shipMemberRepository,
            $this->createMock(ShipMessageRepository::class),
            $this->createMock(UserRepository::class),
            $this->createMock(ShipChatService::class),
            $this->createMock(DailyChallengeService::class),
        );

        self::assertFalse($service->isUserOwner($this->makeUser(), new Ship()));
    }

    public function testUpdateShipMutatesFieldsAndFlushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $chat = $this->createMock(ShipChatService::class);
        $chat->expects(self::once())->method('addSystemMessage')->willReturn(
            $this->createMock(ShipMessage::class)
        );

        $service = new ShipMembershipService(
            $em,
            $this->createMock(ShipMemberRepository::class),
            $this->createMock(ShipMessageRepository::class),
            $this->createMock(UserRepository::class),
            $chat,
            $this->createMock(DailyChallengeService::class),
        );

        $ship = (new Ship())->setTitle('old');
        $editor = $this->makeUser();
        $service->updateShip($ship, $editor, 'newTitle', 'newDescription', 'newNotes');

        self::assertSame('newTitle', $ship->getTitle());
        self::assertSame('newDescription', $ship->getDescription());
        self::assertSame('newNotes', $ship->getInternalNotes());
    }

    private function makeUser(): User
    {
        $level = (new Level())->setName('1')->setExpToNextLevel(220);

        return (new User())
            ->setEmail(sprintf('club_%s@test.local', bin2hex(random_bytes(4))))
            ->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))))
            ->setPassword('hash')
            ->setLevel($level)
            ->setGold(50_000)
            ->setdiamonds(10)
            ->setEnergyPoints(100)
            ->setTrainingPoints(10)
            ->setDuelPoints(10)
            ->setFamePoints(10);
    }
}
