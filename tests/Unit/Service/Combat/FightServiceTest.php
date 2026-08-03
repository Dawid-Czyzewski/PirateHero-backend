<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Combat;

use App\Exception\BusinessRuleException;
use App\Repository\UserRepository;
use App\Service\Combat\FightService;
use App\Service\Combat\TurnBasedDuelResolver;
use App\Service\Progression\QuestProgressService;
use App\Service\Progression\QuestService;
use App\Service\ShopBoosters\CombatStatisticsProvider;
use App\Service\User\SimilarUsersResolver;
use App\Tests\Support\UnconstructedInstance;
use App\Tests\TestDoubles\UserStubFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class FightServiceTest extends TestCase
{
    public function testGetAvailableOpponentsMapsRepositoryResults(): void
    {
        $opponent = UserStubFactory::create(['prefix' => 'rival', 'levelName' => '2']);

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findActivatedUsersExcluding')
            ->willReturn([$opponent]);

        $service = new FightService(
            $this->createMock(EntityManagerInterface::class),
            new SimilarUsersResolver($userRepository),
            UnconstructedInstance::of(QuestProgressService::class),
            UnconstructedInstance::of(QuestService::class),
            $this->createMock(TurnBasedDuelResolver::class),
            $this->combatStatsPassthrough(),
        );

        $out = $service->getAvailableOpponents(UserStubFactory::create(['prefix' => 'hero', 'levelName' => '2']));
        self::assertCount(1, $out);
        self::assertSame($opponent->getId(), $out[0]['id']);
        self::assertSame('2', $out[0]['level']);
        self::assertArrayHasKey('averageSkill', $out[0]);
        self::assertArrayHasKey('totalStats', $out[0]);
        self::assertArrayHasKey('avatarName', $out[0]);
    }

    public function testStartFightThrowsWhenNotEnoughDuelPoints(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('beginTransaction');
        $connection->expects(self::once())->method('rollBack');
        $connection->expects(self::never())->method('commit');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($connection);
        $em->expects(self::exactly(2))
            ->method('lock')
            ->with(self::anything(), LockMode::PESSIMISTIC_WRITE);

        $attacker = UserStubFactory::create(['prefix' => 'atk', 'levelName' => '2', 'duelPoints' => 0]);
        $defender = UserStubFactory::create(['prefix' => 'def', 'levelName' => '2']);

        $service = new FightService(
            $em,
            new SimilarUsersResolver($this->createMock(UserRepository::class)),
            UnconstructedInstance::of(QuestProgressService::class),
            UnconstructedInstance::of(QuestService::class),
            $this->createMock(TurnBasedDuelResolver::class),
            $this->combatStatsPassthrough(),
        );

        $this->expectException(BusinessRuleException::class);
        $this->expectExceptionMessage('notEnoughDuelPoints');
        $service->startFight($attacker, $defender);
    }

    private function combatStatsPassthrough(): CombatStatisticsProvider
    {
        $mock = $this->createMock(CombatStatisticsProvider::class);
        $mock->method('pruneExpiredSessions');
        $mock->method('getCombatStatistics')->willReturn([
            'strength' => 10,
            'agility' => 10,
            'health' => 10,
            'intelligence' => 10,
            'luck' => 10,
        ]);

        return $mock;
    }
}
