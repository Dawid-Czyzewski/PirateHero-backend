<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\User;

use App\Entity\Level;
use App\Entity\User;
use App\Entity\UserBaseStatistics;
use App\Entity\UserSkillPointsPrices;
use App\Enum\UserStatType;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Service\Progression\QuestProgressService;
use App\Service\User\SkillPointsService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class SkillPointsServiceTest extends TestCase
{
    public function testAddFreeSkillPointsRejectsNonPositive(): void
    {
        $service = new SkillPointsService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(QuestProgressService::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('skillPointsAmountMustBePositive');
        $service->addFreeSkillPoints($this->makeUser(), 0);
    }

    public function testAddFreeSkillPointsIncrementsAndFlushes(): void
    {
        $user = $this->makeUser();
        $user->setFreeSkillPointsAvailable(2);

        $em = $this->mockTransactionalEm($user);
        $em->expects(self::once())->method('persist')->with($user);
        $em->expects(self::once())->method('flush');

        $service = new SkillPointsService($em, $this->createMock(QuestProgressService::class));
        $service->addFreeSkillPoints($user, 3);

        self::assertSame(5, $user->getFreeSkillPointsAvailable());
    }

    public function testAddSkillPointThrowsWhenNoBaseStatistics(): void
    {
        $user = $this->makeUser();
        $user->setFreeSkillPointsAvailable(0);

        $service = new SkillPointsService(
            $this->mockTransactionalEm($user),
            $this->createMock(QuestProgressService::class),
        );

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('userStatisticsNotFound');
        $service->addSkillPoint($user, UserStatType::STRENGTH);
    }

    public function testAddSkillPointConsumesFreePoint(): void
    {
        $stats = new UserBaseStatistics();
        $stats->setStrength(1);
        $stats->setAgility(1);
        $stats->setIntelligence(1);
        $stats->setEndurance(10);

        $user = $this->makeUser();
        $user->setFreeSkillPointsAvailable(1);
        $user->setUserBaseStatistics($stats);

        $em = $this->mockTransactionalEm($user);
        $em->method('persist');
        $em->expects(self::once())->method('flush');

        $service = new SkillPointsService($em, $this->createMock(QuestProgressService::class));
        $service->addSkillPoint($user, UserStatType::STRENGTH);

        self::assertSame(0, $user->getFreeSkillPointsAvailable());
        self::assertSame(2, $stats->getStrength());
    }

    public function testAddSkillPointBuysLuckWithGold(): void
    {
        $stats = new UserBaseStatistics();
        $stats->setStrength(5);
        $stats->setAgility(5);
        $stats->setIntelligence(5);
        $stats->setEndurance(5);
        $stats->setLuck(3);

        $prices = new UserSkillPointsPrices();
        $prices->setLuckPointsPrice(10);
        $prices->setStrengthPointsPrice(5);
        $prices->setAgilityPointsPrice(5);
        $prices->setIntelligencePointsPrice(5);
        $prices->setEndurancePointsPrice(5);

        $user = $this->makeUser();
        $user->setFreeSkillPointsAvailable(0);
        $user->setGold(100);
        $user->setUserBaseStatistics($stats);
        $user->setUserSkillPointsPrices($prices);

        $em = $this->mockTransactionalEm($user);
        $em->method('persist');
        $em->expects(self::once())->method('flush');

        $quests = $this->createMock(QuestProgressService::class);
        $quests->expects(self::once())->method('checkAndUpdateProgress');

        $service = new SkillPointsService($em, $quests);
        $service->addSkillPoint($user, UserStatType::LUCK);

        self::assertSame(90, $user->getGold());
        self::assertSame(11, $prices->getLuckPointsPrice());
        self::assertSame(4, $stats->getLuck());
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

    private function makeUser(): User
    {
        $level = (new Level())->setName('4')->setExpToNextLevel(100);

        return (new User())
            ->setEmail(sprintf('sk_%s@test.local', bin2hex(random_bytes(3))))
            ->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))))
            ->setPassword('x')
            ->setLevel($level)
            ->setGold(100_000)
            ->setDiamonds(0)
            ->setEnergyPoints(100)
            ->setTrainingPoints(10)
            ->setDuelPoints(10)
            ->setFamePoints(0);
    }
}
