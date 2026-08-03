<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Ship;

use App\Entity\Level;
use App\Entity\Ship;
use App\Entity\ShipMessage;
use App\Entity\User;
use App\Repository\ShipMessageRepository;
use App\Service\Ship\ShipChatNotifier;
use App\Service\Ship\ShipChatService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class ShipChatServiceTest extends TestCase
{
    public function testAddMessagePersistsAndNotifies(): void
    {
        $ship = (new Ship())->setTitle('C')->setDescription('d');
        $user = $this->makeUser();

        $notifier = $this->createMock(ShipChatNotifier::class);
        $notifier->expects(self::once())->method('publishMessage')->with(self::isInstanceOf(ShipMessage::class));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(ShipMessage::class));
        $em->expects(self::once())->method('flush');

        $repo = $this->createMock(ShipMessageRepository::class);

        $service = new ShipChatService($em, $repo, $notifier);
        $message = $service->addMessage($ship, $user, ' hello ');

        self::assertSame(' hello ', $message->getContent());
        self::assertSame($ship, $message->getShip());
        self::assertSame($user, $message->getAuthor());
    }

    public function testGetMessagesUsesRepositoryQuery(): void
    {
        $ship = (new Ship())->setTitle('X')->setDescription('y');
        $expected = [$this->createMock(ShipMessage::class)];

        $qb = $this->createMock(QueryBuilder::class);
        foreach (['where', 'setParameter', 'orderBy', 'setMaxResults'] as $m) {
            $qb->method($m)->willReturnSelf();
        }
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn($expected);
        $qb->method('getQuery')->willReturn($query);

        $repo = $this->createMock(ShipMessageRepository::class);
        $repo->expects(self::once())->method('createQueryBuilder')->with('m')->willReturn($qb);

        $service = new ShipChatService(
            $this->createMock(EntityManagerInterface::class),
            $repo,
            $this->createMock(ShipChatNotifier::class),
        );

        self::assertSame($expected, $service->getMessages($ship, 25));
    }

    private function makeUser(): User
    {
        $level = (new Level())->setName('1')->setExpToNextLevel(100);

        return (new User())
            ->setEmail(sprintf('cc_%s@test.local', bin2hex(random_bytes(3))))
            ->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))))
            ->setPassword('x')
            ->setLevel($level)
            ->setGold(0)
            ->setdiamonds(0)
            ->setEnergyPoints(100)
            ->setTrainingPoints(10)
            ->setDuelPoints(10)
            ->setFamePoints(0);
    }
}
