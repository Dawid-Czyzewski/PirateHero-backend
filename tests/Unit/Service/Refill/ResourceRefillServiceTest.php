<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Refill;

use App\Entity\Level;
use App\Entity\Training;
use App\Entity\User;
use App\Entity\UserActualActivity;
use App\Entity\UserCapacities;
use App\Entity\UserRefill;
use App\Enum\RefillType;
use App\Exception\BusinessRuleException;
use App\Repository\UserRefillRepository;
use App\Service\Economy\BoosterService;
use App\Service\Refill\ResourceRefillService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ResourceRefillServiceTest extends TestCase
{
    public function testCalculateRefillCostFirstAndSecond(): void
    {
        $user = $this->makeUserWithLevel('7');
        $service = $this->makeService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(BoosterService::class),
            $this->createMock(UserRefillRepository::class),
        );

        self::assertSame(700, $service->calculateRefillCost($user, 1));
        self::assertSame(1400, $service->calculateRefillCost($user, 2));
    }

    public function testCalculateRefillCostThrowsWhenUserHasNoLevel(): void
    {
        $user = $this->makeUserWithOptionalLevel(null);

        $service = $this->makeService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(BoosterService::class),
            $this->createMock(UserRefillRepository::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('userLevelNotFound');
        $service->calculateRefillCost($user, 1);
    }

    public function testRefillEnergyThrowsWhenMissionActive(): void
    {
        $user = $this->makeUserWithLevel('3');
        $activity = new UserActualActivity();
        $activity->setUser($user);
        $user->setCurrentActivity($activity);

        $service = $this->makeService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(BoosterService::class),
            $this->createMock(UserRefillRepository::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('refillNotAllowedDuringActiveMission');
        $service->refill($user, RefillType::ENERGY);
    }

    public function testRefillEnergyThrowsWhenEnergyAlreadyFull(): void
    {
        $user = $this->makeUserWithEnergy(50, 50);
        $userRefill = $this->makeUserRefill($user, RefillType::ENERGY, 0);

        $service = $this->makeService(
            $this->mockTransactionalEm($user),
            $this->makeBoosterMock(),
            $this->makeRepoReturning($userRefill),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('energyAlreadyFull');
        $service->refill($user, RefillType::ENERGY);
    }

    public function testRefillTrainingThrowsWhenTrainingActive(): void
    {
        $user = $this->makeUserWithLevel('4');
        $activity = new UserActualActivity();
        $activity->setUser($user);
        $activity->setTraining($this->createMock(Training::class));
        $user->setCurrentActivity($activity);

        $service = $this->makeService(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(BoosterService::class),
            $this->createMock(UserRefillRepository::class),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('refillNotAllowedDuringActiveTraining');
        $service->refill($user, RefillType::TRAINING);
    }

    public function testRefillTrainingThrowsWhenTrainingPointsAlreadyFull(): void
    {
        $user = $this->makeUserWithTrainingCap(8, 8);
        $userRefill = $this->makeUserRefill($user, RefillType::TRAINING, 0);

        $service = $this->makeService(
            $this->mockTransactionalEm($user),
            $this->makeBoosterMock(),
            $this->makeRepoReturning($userRefill),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('trainingPointsAlreadyFull');
        $service->refill($user, RefillType::TRAINING);
    }

    public function testRefillFightThrowsWhenDailyLimitExhausted(): void
    {
        $user = $this->makeUserWithLevel('1');
        $user->setGold(9_999);
        $user->setDuelPoints(0);
        $userRefill = $this->makeUserRefill($user, RefillType::FIGHT, 2);

        $service = $this->makeService(
            $this->mockTransactionalEm($user),
            $this->makeBoosterMock(),
            $this->makeRepoReturning($userRefill),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('refillDailyLimitExhausted');
        $service->refill($user, RefillType::FIGHT);
    }

    public function testRefillFightThrowsWhenInsufficientGold(): void
    {
        $user = $this->makeUserWithLevel('5');
        $user->setGold(0);
        $user->setDuelPoints(0);
        $userRefill = $this->makeUserRefill($user, RefillType::FIGHT, 0);

        $service = $this->makeService(
            $this->mockTransactionalEm($user),
            $this->makeBoosterMock(),
            $this->makeRepoReturning($userRefill),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('insufficientGold');
        $service->refill($user, RefillType::FIGHT);
    }

    public function testCanRefillResetsDailyCountOnNewDay(): void
    {
        $user = $this->makeUserWithEnergy(10, 50);
        $userRefill = $this->makeUserRefill($user, RefillType::ENERGY, 2);
        $userRefill->setLastRefillDate(new \DateTime('yesterday'));

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('refresh');
        $em->expects(self::atLeastOnce())->method('persist')->with($userRefill);
        $em->expects(self::atLeastOnce())->method('flush');

        $service = $this->makeService($em, $this->makeBoosterMock(), $this->makeRepoReturning($userRefill));

        $status = $service->canRefill($user, RefillType::ENERGY);

        self::assertSame(0, $userRefill->getRefillCount());
        self::assertSame(2, $status['refillsRemaining']);
        self::assertTrue($status['canRefill']);
    }

    private function makeService(
        EntityManagerInterface $em,
        BoosterService $booster,
        UserRefillRepository $repo,
    ): ResourceRefillService {
        return new ResourceRefillService($em, $booster, $repo);
    }

    private function mockTransactionalEm(User $user): EntityManagerInterface
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('beginTransaction');
        $connection->method('commit');
        $connection->method('rollBack');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->method('find')->willReturn($user);
        $em->method('refresh');
        $em->method('flush');
        $em->method('persist');

        return $em;
    }

    private function makeBoosterMock(): BoosterService
    {
        $booster = $this->createMock(BoosterService::class);
        $booster->method('calculateActualCapacity');

        return $booster;
    }

    private function makeRepoReturning(UserRefill $userRefill): UserRefillRepository
    {
        $repo = $this->createMock(UserRefillRepository::class);
        $repo->method('findByUserAndType')->willReturn($userRefill);

        return $repo;
    }

    private function makeUserRefill(User $user, RefillType $type, int $count): UserRefill
    {
        $userRefill = new UserRefill();
        $userRefill->setUser($user);
        $userRefill->setType($type);
        $userRefill->setRefillCount($count);
        $userRefill->setLastRefillDate(new \DateTime());

        return $userRefill;
    }

    private function makeUserWithLevel(string $levelName): User
    {
        $level = (new Level())->setName($levelName)->setExpToNextLevel(100);

        return (new User())
            ->setEmail(sprintf('rr_%s@test.local', bin2hex(random_bytes(3))))
            ->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))))
            ->setPassword('x')
            ->setLevel($level)
            ->setGold(1000)
            ->setDiamonds(0)
            ->setEnergyPoints(100)
            ->setTrainingPoints(10)
            ->setDuelPoints(0)
            ->setFamePoints(0);
    }

    private function makeUserWithOptionalLevel(?Level $level): User
    {
        $user = (new User())
            ->setEmail(sprintf('rr_%s@test.local', bin2hex(random_bytes(3))))
            ->setUsername(sprintf('u_%s', bin2hex(random_bytes(3))))
            ->setPassword('x')
            ->setGold(100)
            ->setDiamonds(0)
            ->setEnergyPoints(100)
            ->setTrainingPoints(10)
            ->setDuelPoints(10)
            ->setFamePoints(0);

        if ($level !== null) {
            $user->setLevel($level);
        }

        return $user;
    }

    private function makeUserWithEnergy(int $energy, int $maxEnergy): User
    {
        $user = $this->makeUserWithLevel('3');
        $user->setGold(9_999);
        $user->setEnergyPoints($energy);

        $capacities = new UserCapacities();
        $capacities->setUser($user);
        $capacities->setEnergyPoints($maxEnergy);
        $user->setUserCapacities($capacities);

        return $user;
    }

    private function makeUserWithTrainingCap(int $current, int $max): User
    {
        $user = $this->makeUserWithLevel('4');
        $user->setGold(9_999);
        $user->setTrainingPoints($current);

        $capacities = new UserCapacities();
        $capacities->setUser($user);
        $capacities->setTrainingPoints($max);
        $user->setUserCapacities($capacities);

        return $user;
    }
}
