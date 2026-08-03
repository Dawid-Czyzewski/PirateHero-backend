<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Dungeon\DungeonCatalog;
use App\Entity\Level;
use App\Entity\PlayerTitle;
use App\Entity\User;
use App\Entity\UserTitle;
use App\Enum\TitleUnlockType;
use App\Repository\PlayerTitleRepository;
use App\Repository\UserBestiaryEntryRepository;
use App\Repository\UserDungeonProgressRepository;
use App\Repository\UserTitleRepository;
use App\Service\Progression\TitleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class TitleServiceTest extends TestCase
{
    public function testSyncUnlocksCreatesRookieTitleForAnyUser(): void
    {
        $user = $this->makeUser(levelName: '1', gold: 0);
        $rookie = $this->makeTitle('rookie', TitleUnlockType::GAME_START);

        $playerTitleRepo = $this->createMock(PlayerTitleRepository::class);
        $playerTitleRepo->method('findAllOrdered')->willReturn([$rookie]);

        $userTitleRepo = $this->createMock(UserTitleRepository::class);
        $userTitleRepo->method('getUnlockedMapForUser')->willReturn([]);

        $dungeonRepo = $this->createMock(UserDungeonProgressRepository::class);
        $dungeonRepo->method('getProgressMapForUser')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::callback(
            static fn ($entity): bool => $entity instanceof UserTitle
        ));
        $em->expects(self::once())->method('flush');

        $service = $this->makeService($em, $playerTitleRepo, $userTitleRepo, $dungeonRepo);
        $service->syncUnlocks($user);
    }

    public function testSyncUnlocksCreatesVeteranWhenLevelReached(): void
    {
        $user = $this->makeUser(levelName: '25', gold: 0);
        $veteran = $this->makeTitle('veteran', TitleUnlockType::LEVEL_REACHED, unlockValue: 25);

        $playerTitleRepo = $this->createMock(PlayerTitleRepository::class);
        $playerTitleRepo->method('findAllOrdered')->willReturn([$veteran]);

        $userTitleRepo = $this->createMock(UserTitleRepository::class);
        $userTitleRepo->method('getUnlockedMapForUser')->willReturn([]);

        $dungeonRepo = $this->createMock(UserDungeonProgressRepository::class);
        $dungeonRepo->method('getProgressMapForUser')->willReturn([]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist');
        $em->expects(self::once())->method('flush');

        $service = $this->makeService($em, $playerTitleRepo, $userTitleRepo, $dungeonRepo);
        $service->syncUnlocks($user);
    }

    public function testSyncUnlocksCreatesDungeonTitleWhenCleared(): void
    {
        $user = $this->makeUser(levelName: '15', gold: 0);
        $cryptHunter = $this->makeTitle(
            'crypt_hunter',
            TitleUnlockType::DUNGEON_COMPLETED,
            unlockDungeonId: 'krypta'
        );

        $playerTitleRepo = $this->createMock(PlayerTitleRepository::class);
        $playerTitleRepo->method('findAllOrdered')->willReturn([$cryptHunter]);

        $userTitleRepo = $this->createMock(UserTitleRepository::class);
        $userTitleRepo->method('getUnlockedMapForUser')->willReturn([]);

        $dungeonRepo = $this->createMock(UserDungeonProgressRepository::class);
        $dungeonRepo->method('getProgressMapForUser')->willReturn([
            'krypta' => DungeonCatalog::STAGES_PER_DUNGEON,
        ]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist');
        $em->expects(self::once())->method('flush');

        $service = $this->makeService($em, $playerTitleRepo, $userTitleRepo, $dungeonRepo);
        $service->syncUnlocks($user);
    }

    public function testBuildEquippedTitleDtoReturnsNullForMissingTitle(): void
    {
        $service = $this->makeService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(PlayerTitleRepository::class),
            $this->createMock(UserTitleRepository::class),
            $this->createMock(UserDungeonProgressRepository::class),
        );

        self::assertNull($service->buildEquippedTitleDto(null));
    }

    private function makeService(
        EntityManagerInterface $em,
        PlayerTitleRepository $playerTitleRepo,
        UserTitleRepository $userTitleRepo,
        UserDungeonProgressRepository $dungeonRepo,
    ): TitleService {
        $bestiaryRepo = $this->createMock(UserBestiaryEntryRepository::class);
        $bestiaryRepo->method('countForUser')->willReturn(0);

        return new TitleService($em, $playerTitleRepo, $userTitleRepo, $dungeonRepo, $bestiaryRepo);
    }

    private function makeUser(string $levelName, int $gold): User
    {
        $level = new Level();
        $level->setName($levelName);
        $level->setExpToNextLevel(100);

        $user = new User();
        $user->setLevel($level);
        $user->setGold($gold);

        return $user;
    }

    private function makeTitle(
        string $code,
        TitleUnlockType $unlockType,
        ?int $unlockValue = null,
        ?string $unlockDungeonId = null,
    ): PlayerTitle {
        $title = new PlayerTitle();
        $title->setCode($code);
        $title->setNameKey('titles.'.$code.'.name');
        $title->setDescriptionKey('titles.'.$code.'.unlockHint');
        $title->setUnlockType($unlockType);
        $title->setUnlockValue($unlockValue);
        $title->setUnlockDungeonId($unlockDungeonId);
        $title->setSortOrder(1);

        return $title;
    }
}
