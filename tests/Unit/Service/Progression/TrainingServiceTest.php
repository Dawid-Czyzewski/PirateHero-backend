<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Progression;

use App\Entity\Level;
use App\Entity\Training;
use App\Entity\User;
use App\Entity\UserActualActivity;
use App\Entity\UserBaseStatistics;
use App\Enum\UserStatType;
use App\Exception\BusinessRuleException;
use App\Exception\ResourceNotFoundException;
use App\Service\Progression\TimedActivity\OwnedTimedActivityResolver;
use App\Service\Progression\TimedActivity\TimedActivityLifecycle;
use App\Service\Progression\TrainingRewardCalculator;
use App\Service\Progression\TrainingService;
use App\Service\Progression\UserWriteLockExecutor;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

final class TrainingServiceTest extends TestCase
{
    public function testResolveOwnedTrainingThrowsWhenNotFound(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $repo->method('find')->willReturn(null);
        $em->method('getRepository')->willReturn($repo);

        $service = $this->makeService($em);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('trainingNotFound');
        $service->resolveOwnedTraining($this->makeUser(), 123);
    }

    public function testStartTrainingThrowsWhenActivityInProgress(): void
    {
        $user = $this->makeUser();
        $user->setCurrentActivity(new UserActualActivity());
        $training = $this->makeTraining($user);

        $service = $this->makeService($this->mockTransactionalEm($user));

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('activityAlreadyInProgress');
        $service->startTraining($user, $training);
    }

    public function testStartTrainingThrowsWhenNotEnoughPoints(): void
    {
        $user = $this->makeUser();
        $user->setTrainingPoints(0);
        $training = $this->makeTraining($user)->setTrainingPointsCost(5);

        $service = $this->makeService($this->mockTransactionalEm($user));

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('notEnoughTrainingPoints');
        $service->startTraining($user, $training);
    }

    public function testGenerateTrainingsForUserCreatesOneTrainingPerStatType(): void
    {
        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(static function ($entity) use (&$persisted): void {
            if ($entity instanceof Training) {
                $persisted[] = $entity;
            }
        });
        $em->method('flush');

        $service = $this->makeService($em);
        $service->generateTrainingsForUser($this->makeUser());

        self::assertCount(5, $persisted);
        $typeValues = array_map(
            static fn (Training $t) => $t->getStatType()->value,
            $persisted
        );
        self::assertCount(5, array_unique($typeValues, \SORT_STRING));
        foreach ($persisted as $training) {
            self::assertSame(2, $training->getSkillPointsReward());
            self::assertSame(2, $training->getTrainingPointsCost());
        }
    }

    public function testCancelTrainingRefundsCostAndClearsActivity(): void
    {
        $user = $this->makeUser();
        $user->setTrainingPoints(8);
        $training = $this->makeTraining($user)->setTrainingPointsCost(2);
        $activity = new UserActualActivity();
        $activity->setUser($user);
        $activity->setTraining($training);
        $user->setCurrentActivity($activity);

        $em = $this->mockTransactionalEm($user);
        $em->expects(self::once())->method('remove')->with($activity);
        $em->method('persist');
        $em->method('flush');

        $service = $this->makeService($em);
        $service->cancelTraining($user);

        self::assertNull($user->getCurrentActivity());
        self::assertSame(10, $user->getTrainingPoints());
    }

    public function testCompleteTrainingThrowsWhenDurationNotElapsed(): void
    {
        $user = $this->makeUser();
        $stats = new UserBaseStatistics();
        $stats->setUser($user);
        $stats->setStrength(5);
        $user->setUserBaseStatistics($stats);

        $training = $this->makeTraining($user)->setDurationInSeconds(3_600);
        $activity = new UserActualActivity();
        $activity->setUser($user);
        $activity->setTraining($training);
        $activity->setStartTime(new \DateTime());
        $user->setCurrentActivity($activity);

        $service = $this->makeService($this->mockTransactionalEm($user));

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('trainingNotComplete');
        $service->completeTraining($user);
    }

    private function makeService(EntityManagerInterface $em): TrainingService
    {
        return new TrainingService(
            $em,
            new UserWriteLockExecutor($em),
            new TimedActivityLifecycle($em),
            new OwnedTimedActivityResolver($em),
            new TrainingRewardCalculator(),
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

    private function makeUser(): User
    {
        $level = (new Level())->setName('1')->setExpToNextLevel(220);

        return (new User())
            ->setEmail(sprintf('training_%s@test.local', bin2hex(random_bytes(4))))
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

    private function makeTraining(User $user): Training
    {
        return (new Training())
            ->setTitle('t')
            ->setDescription('d')
            ->setDurationInSeconds(600)
            ->setTrainingPointsCost(2)
            ->setSkillPointsReward(1)
            ->setStatType(UserStatType::STRENGTH)
            ->setUser($user);
    }
}
