<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Bestiary;

use App\Bestiary\BestiaryCatalog;
use App\Entity\User;
use App\Entity\UserBestiaryEntry;
use App\Repository\UserBestiaryEntryRepository;
use App\Service\Bestiary\BestiaryService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class BestiaryServiceTest extends TestCase
{
    public function testGetForUserMarksUndiscoveredEntries(): void
    {
        $user = new User();
        $user->setEmail('bestiary@test.local');
        $user->setUsername('captain');
        $user->setPassword('hash');

        $repo = $this->createMock(UserBestiaryEntryRepository::class);
        $repo->method('getDiscoveredMapForUser')->willReturn([]);

        $service = new BestiaryService(
            $this->createMock(EntityManagerInterface::class),
            $repo,
        );

        $result = $service->getForUser($user);
        self::assertCount(50, $result['entries']);
        self::assertFalse($result['entries'][0]['discovered']);
        self::assertNull($result['entries'][0]['defeatedAt']);
    }

    public function testRecordDefeatPersistsNewEntry(): void
    {
        $user = new User();
        $user->setEmail('bestiary2@test.local');
        $user->setUsername('captain2');
        $user->setPassword('hash');

        $repo = $this->createMock(UserBestiaryEntryRepository::class);
        $repo->method('findOneForUserDungeonStage')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::isInstanceOf(UserBestiaryEntry::class));

        $service = new BestiaryService($em, $repo);
        $service->recordDefeat($user, 'krypta', 1);
    }

    public function testRecordDefeatSkipsExistingEntry(): void
    {
        $user = new User();
        $user->setEmail('bestiary3@test.local');
        $user->setUsername('captain3');
        $user->setPassword('hash');

        $existing = new UserBestiaryEntry();
        $repo = $this->createMock(UserBestiaryEntryRepository::class);
        $repo->method('findOneForUserDungeonStage')->willReturn($existing);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');

        $service = new BestiaryService($em, $repo);
        $service->recordDefeat($user, 'krypta', 1);
    }

    public function testCatalogContainsFiftyEntries(): void
    {
        self::assertCount(50, BestiaryCatalog::entries());
    }
}
