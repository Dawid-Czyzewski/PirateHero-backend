<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Domain\Constants\CompanionConstants;
use App\Entity\CompanionTemplate;
use App\Entity\Level;
use App\Entity\Mission;
use App\Entity\User;
use App\Entity\UserActualActivity;
use App\Entity\UserCompanion;
use App\Exception\BusinessRuleException;
use App\Repository\CompanionTemplateRepository;
use App\Repository\UserCompanionRepository;
use App\Service\Progression\CompanionService;
use App\Service\Progression\UserWriteLockExecutor;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class CompanionServiceTest extends TestCase
{
    public function testEnsureParrotUnlockCreatesOwnershipAtLevelFive(): void
    {
        $user = $this->makeUser(5);
        $parrot = $this->makeParrotTemplate();

        $templateRepo = $this->createMock(CompanionTemplateRepository::class);
        $templateRepo->method('findOneByCode')->with(CompanionConstants::CODE_PARROT)->willReturn($parrot);

        $userCompanionRepo = $this->createMock(UserCompanionRepository::class);
        $userCompanionRepo->method('findOneForUserAndTemplate')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with(self::callback(
            static fn ($entity): bool => $entity instanceof UserCompanion
                && $entity->getLevel() === 1
                && $entity->getCompanionTemplate() === $parrot
        ));
        $em->expects(self::once())->method('flush');

        $service = $this->makeService($em, $templateRepo, $userCompanionRepo);
        $service->ensureParrotUnlock($user);
    }

    public function testEnsureParrotUnlockSkippedBelowLevelFive(): void
    {
        $user = $this->makeUser(4);

        $templateRepo = $this->createMock(CompanionTemplateRepository::class);
        $templateRepo->expects(self::never())->method('findOneByCode');

        $userCompanionRepo = $this->createMock(UserCompanionRepository::class);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('persist');

        $service = $this->makeService($em, $templateRepo, $userCompanionRepo);
        $service->ensureParrotUnlock($user);
    }

    public function testFeedSpendsGoldAndIncreasesLevel(): void
    {
        $user = $this->makeUser(5);
        $user->setGold(200);
        $parrot = $this->makeParrotTemplate();
        $owned = $this->makeOwned($user, $parrot, level: 1);

        $templateRepo = $this->createMock(CompanionTemplateRepository::class);
        $templateRepo->method('findOneByCode')->willReturn($parrot);
        $templateRepo->method('findAllOrdered')->willReturn([$parrot]);

        $userCompanionRepo = $this->createMock(UserCompanionRepository::class);
        $userCompanionRepo->method('findOneForUserAndTemplate')->willReturn($owned);
        $userCompanionRepo->method('getOwnedMapForUser')->willReturn([CompanionConstants::CODE_PARROT => $owned]);

        $em = $this->mockTransactionalEm($user);
        $em->expects(self::atLeastOnce())->method('persist');
        $em->expects(self::atLeastOnce())->method('flush');

        $service = $this->makeService($em, $templateRepo, $userCompanionRepo);
        $status = $service->feed($user, CompanionConstants::CODE_PARROT);

        self::assertSame(150, $user->getGold());
        self::assertSame(2, $owned->getLevel());
        self::assertNotNull($owned->getLastFedAt());
        self::assertSame(CompanionConstants::CODE_PARROT, $status['companions'][0]['code']);
        self::assertSame(2, $status['companions'][0]['level']);
    }

    public function testFeedThrowsWhenOnCooldown(): void
    {
        $user = $this->makeUser(5);
        $user->setGold(200);
        $parrot = $this->makeParrotTemplate();
        $owned = $this->makeOwned($user, $parrot, level: 2);
        $owned->setLastFedAt(new \DateTimeImmutable('-1 hour'));

        $templateRepo = $this->createMock(CompanionTemplateRepository::class);
        $templateRepo->method('findOneByCode')->willReturn($parrot);

        $userCompanionRepo = $this->createMock(UserCompanionRepository::class);
        $userCompanionRepo->method('findOneForUserAndTemplate')->willReturn($owned);

        $service = $this->makeService($this->mockTransactionalEm($user), $templateRepo, $userCompanionRepo);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('companionFeedOnCooldown');
        $service->feed($user, CompanionConstants::CODE_PARROT);
    }

    public function testUseAbilityRequiresActiveMission(): void
    {
        $user = $this->makeUser(5);
        $parrot = $this->makeParrotTemplate();
        $owned = $this->makeOwned($user, $parrot, level: 1);
        $user->setEquippedCompanion($parrot);

        $templateRepo = $this->createMock(CompanionTemplateRepository::class);
        $templateRepo->method('findOneByCode')->willReturn($parrot);

        $userCompanionRepo = $this->createMock(UserCompanionRepository::class);
        $userCompanionRepo->method('findOneForUserAndTemplate')->willReturn($owned);

        $service = $this->makeService($this->mockTransactionalEm($user), $templateRepo, $userCompanionRepo);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('companionAbilityRequiresActiveMission');
        $service->useAbility($user);
    }

    public function testUseAbilityShortensMissionStartTimeAndSetsCooldown(): void
    {
        $user = $this->makeUser(5);
        $parrot = $this->makeParrotTemplate();
        $owned = $this->makeOwned($user, $parrot, level: 1);
        $user->setEquippedCompanion($parrot);

        $mission = new Mission();
        $activity = new UserActualActivity();
        $activity->setUser($user);
        $activity->setMission($mission);
        $originalStart = new \DateTime('-30 minutes');
        $activity->setStartTime(clone $originalStart);
        $user->setCurrentActivity($activity);

        $templateRepo = $this->createMock(CompanionTemplateRepository::class);
        $templateRepo->method('findOneByCode')->willReturn($parrot);
        $templateRepo->method('findAllOrdered')->willReturn([$parrot]);

        $userCompanionRepo = $this->createMock(UserCompanionRepository::class);
        $userCompanionRepo->method('findOneForUserAndTemplate')->willReturn($owned);
        $userCompanionRepo->method('getOwnedMapForUser')->willReturn([CompanionConstants::CODE_PARROT => $owned]);

        $em = $this->mockTransactionalEm($user);
        $em->expects(self::atLeastOnce())->method('persist');
        $em->expects(self::atLeastOnce())->method('flush');

        $service = $this->makeService($em, $templateRepo, $userCompanionRepo);
        $service->useAbility($user);

        $expected = (clone $originalStart)->modify('-'.CompanionConstants::ABILITY_SHORTEN_MISSION_MINUTES.' minutes');
        self::assertEquals($expected, $activity->getStartTime());
        self::assertNotNull($owned->getLastAbilityAt());
    }

    public function testUseAbilityThrowsWhenOnCooldown(): void
    {
        $user = $this->makeUser(5);
        $parrot = $this->makeParrotTemplate();
        $owned = $this->makeOwned($user, $parrot, level: 1);
        $owned->setLastAbilityAt(new \DateTimeImmutable('-1 hour'));
        $user->setEquippedCompanion($parrot);

        $mission = new Mission();
        $activity = new UserActualActivity();
        $activity->setUser($user);
        $activity->setMission($mission);
        $activity->setStartTime(new \DateTime('-30 minutes'));
        $user->setCurrentActivity($activity);

        $templateRepo = $this->createMock(CompanionTemplateRepository::class);
        $templateRepo->method('findOneByCode')->willReturn($parrot);

        $userCompanionRepo = $this->createMock(UserCompanionRepository::class);
        $userCompanionRepo->method('findOneForUserAndTemplate')->willReturn($owned);

        $service = $this->makeService($this->mockTransactionalEm($user), $templateRepo, $userCompanionRepo);

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('companionAbilityOnCooldown');
        $service->useAbility($user);
    }

    public function testWorkGoldBonusPercentUsesEquippedCompanionLevel(): void
    {
        $user = $this->makeUser(5);
        $parrot = $this->makeParrotTemplate();
        $owned = $this->makeOwned($user, $parrot, level: 7);
        $user->setEquippedCompanion($parrot);

        $userCompanionRepo = $this->createMock(UserCompanionRepository::class);
        $userCompanionRepo->method('findOneForUserAndTemplate')->willReturn($owned);

        $service = $this->makeService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(CompanionTemplateRepository::class),
            $userCompanionRepo,
        );

        self::assertSame(7, $service->workGoldBonusPercentForUser($user));
        self::assertSame(0, CompanionConstants::workGoldBonusPercent(0));
        self::assertSame(1, CompanionConstants::workGoldBonusPercent(1));
        self::assertSame(10, CompanionConstants::workGoldBonusPercent(10));
    }

    public function testWorkGoldBonusPercentZeroWhenNothingEquipped(): void
    {
        $user = $this->makeUser(5);
        $service = $this->makeService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(CompanionTemplateRepository::class),
            $this->createMock(UserCompanionRepository::class),
        );

        self::assertSame(0, $service->workGoldBonusPercentForUser($user));
    }

    private function makeService(
        EntityManagerInterface $em,
        CompanionTemplateRepository $templateRepo,
        UserCompanionRepository $userCompanionRepo,
    ): CompanionService {
        return new CompanionService(
            $em,
            $templateRepo,
            $userCompanionRepo,
            new UserWriteLockExecutor($em),
        );
    }

    private function mockTransactionalEm(User $user): EntityManagerInterface
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction');
        $connection->method('commit');
        $connection->method('rollBack');

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

        return $em;
    }

    private function makeUser(int $levelName): User
    {
        $level = (new Level())->setName((string) $levelName)->setExpToNextLevel(220);

        return (new User())
            ->setEmail(sprintf('companion_%s@test.local', bin2hex(random_bytes(4))))
            ->setUsername(sprintf('c_%s', bin2hex(random_bytes(3))))
            ->setPassword('hash')
            ->setLevel($level)
            ->setGold(100)
            ->setdiamonds(10)
            ->setEnergyPoints(100)
            ->setTrainingPoints(10)
            ->setDuelPoints(10)
            ->setFamePoints(10);
    }

    private function makeParrotTemplate(): CompanionTemplate
    {
        return (new CompanionTemplate())
            ->setCode(CompanionConstants::CODE_PARROT)
            ->setNameKey('companions.parrot.name')
            ->setDescriptionKey('companions.parrot.description')
            ->setImageKey('companions.parrot.image')
            ->setSortOrder(1);
    }

    private function makeOwned(User $user, CompanionTemplate $template, int $level): UserCompanion
    {
        return (new UserCompanion())
            ->setUser($user)
            ->setCompanionTemplate($template)
            ->setUnlockedAt(new \DateTimeImmutable())
            ->setLevel($level);
    }
}
