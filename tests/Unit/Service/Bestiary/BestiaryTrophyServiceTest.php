<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Bestiary;

use App\Bestiary\BestiaryTrophyCatalog;
use App\Entity\Level;
use App\Entity\User;
use App\Entity\UserBestiaryTrophy;
use App\Exception\BusinessRuleException;
use App\Repository\UserBestiaryEntryRepository;
use App\Repository\UserBestiaryTrophyRepository;
use App\Service\Bestiary\BestiaryTrophyService;
use App\Service\Progression\TitleService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class BestiaryTrophyServiceTest extends TestCase
{
    public function testSyncUnlocksReachedThresholds(): void
    {
        $user = $this->makeUser();
        $entryRepo = $this->createMock(UserBestiaryEntryRepository::class);
        $entryRepo->method('countForUser')->willReturn(25);
        $trophyRepo = $this->createMock(UserBestiaryTrophyRepository::class);
        $trophyRepo->method('getMapForUser')->willReturn([]);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::exactly(2))->method('persist')->willReturnCallback(
            static function ($entity) use (&$persisted): void {
                if ($entity instanceof UserBestiaryTrophy) {
                    $persisted[] = $entity->getTrophyCode();
                }
            }
        );
        $em->expects(self::once())->method('flush');

        $service = new BestiaryTrophyService(
            $em,
            $entryRepo,
            $trophyRepo,
            $this->createMock(TitleService::class),
        );
        $service->sync($user);

        self::assertSame(['bestiary_trophy_25', 'bestiary_trophy_50'], $persisted);
    }

    public function testGetStatusReportsClaimable(): void
    {
        $user = $this->makeUser();
        $row = new UserBestiaryTrophy();
        $row->setUser($user);
        $row->setTrophyCode('bestiary_trophy_25');
        $row->setRewardClaimed(false);

        $entryRepo = $this->createMock(UserBestiaryEntryRepository::class);
        $entryRepo->method('countForUser')->willReturn(13);
        $trophyRepo = $this->createMock(UserBestiaryTrophyRepository::class);
        $trophyRepo->method('getMapForUser')->willReturn(['bestiary_trophy_25' => $row]);

        $em = $this->createMock(EntityManagerInterface::class);
        $service = new BestiaryTrophyService(
            $em,
            $entryRepo,
            $trophyRepo,
            $this->createMock(TitleService::class),
        );

        $status = $service->getStatus($user);
        self::assertSame(13, $status['discoveredCount']);
        self::assertSame(BestiaryTrophyCatalog::TOTAL_ENTRIES, $status['total']);
        self::assertSame(1, $status['unclaimedCount']);
        self::assertTrue($status['trophies'][0]['canClaim']);
    }

    public function testClaimThrowsWhenNotUnlocked(): void
    {
        $user = $this->makeUser();
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('rollBack');

        $entryRepo = $this->createMock(UserBestiaryEntryRepository::class);
        $entryRepo->method('countForUser')->willReturn(0);
        $trophyRepo = $this->createMock(UserBestiaryTrophyRepository::class);
        $trophyRepo->method('getMapForUser')->willReturn([]);
        $trophyRepo->method('findOneForUserAndCode')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('find')->willReturnCallback(
            static function (string $class, mixed $id, ?int $lockMode = null) use ($user) {
                if ($class === User::class && $lockMode === LockMode::PESSIMISTIC_WRITE) {
                    return $user;
                }

                return null;
            }
        );

        $service = new BestiaryTrophyService(
            $em,
            $entryRepo,
            $trophyRepo,
            $this->createMock(TitleService::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('bestiaryTrophyNotUnlocked');
        $service->claim($user, 'bestiary_trophy_25');
    }

    private function makeUser(): User
    {
        $level = (new Level())->setName('10')->setExpToNextLevel(100);

        return (new User())
            ->setEmail('trophy@test.local')
            ->setUsername('trophy')
            ->setPassword('hash')
            ->setLevel($level)
            ->setGold(1000)
            ->setDiamonds(5);
    }
}
