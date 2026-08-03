<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\User;

use App\Repository\ShipMemberRepository;
use App\Repository\ShipRepository;
use App\Repository\UserRepository;
use App\Service\Progression\TitleService;
use App\Service\User\RankingService;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class RankingServiceTest extends TestCase
{
    public function testGetPlayersRankingReturnsEmptyPage(): void
    {
        $qbCount = $this->makeQueryBuilderMock();
        $queryCount = $this->createMock(Query::class);
        $queryCount->method('getSingleScalarResult')->willReturn(0);
        $qbCount->method('getQuery')->willReturn($queryCount);

        $qbList = $this->makeQueryBuilderMock();
        $queryList = $this->createMock(Query::class);
        $queryList->method('getResult')->willReturn([]);
        $qbList->method('getQuery')->willReturn($queryList);

        $i = 0;
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('createQueryBuilder')->willReturnCallback(static function () use (&$i, $qbCount, $qbList) {
            ++$i;

            return $i === 1 ? $qbCount : $qbList;
        });

        $shipMemberRepo = $this->createMock(ShipMemberRepository::class);

        $service = new RankingService(
            $userRepo,
            $this->createMock(ShipRepository::class),
            $shipMemberRepo,
            $this->createMock(TitleService::class),
        );

        $response = $service->getPlayersRanking(1, 20, 'famePoints', 'DESC');
        self::assertSame([], $response->items);
        self::assertSame(1, $response->pagination->page);
        self::assertSame(0, $response->pagination->total);
    }

    public function testGetPlayersRankingAppliesUsernameSearchFilter(): void
    {
        $qbCount = $this->makeQueryBuilderMock();
        $queryCount = $this->createMock(Query::class);
        $queryCount->method('getSingleScalarResult')->willReturn(0);
        $qbCount->method('getQuery')->willReturn($queryCount);
        $qbCount->expects(self::exactly(2))
            ->method('andWhere')
            ->willReturnCallback(static function (string $clause) use ($qbCount): QueryBuilder {
                static $calls = 0;
                ++$calls;
                if ($calls === 1) {
                    self::assertSame('u.activateToken IS NULL', $clause);
                } else {
                    self::assertSame('LOWER(u.username) LIKE :rankingSearch', $clause);
                }

                return $qbCount;
            });
        $qbCount->expects(self::once())
            ->method('setParameter')
            ->with('rankingSearch', '%kap%')
            ->willReturnSelf();

        $qbList = $this->makeQueryBuilderMock();
        $queryList = $this->createMock(Query::class);
        $queryList->method('getResult')->willReturn([]);
        $qbList->method('getQuery')->willReturn($queryList);
        $qbList->method('andWhere')->willReturnSelf();
        $qbList->method('setParameter')->willReturnSelf();

        $i = 0;
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('createQueryBuilder')->willReturnCallback(static function () use (&$i, $qbCount, $qbList) {
            ++$i;

            return $i === 1 ? $qbCount : $qbList;
        });

        $service = new RankingService(
            $userRepo,
            $this->createMock(ShipRepository::class),
            $this->createMock(ShipMemberRepository::class),
            $this->createMock(TitleService::class),
        );

        $service->getPlayersRanking(1, 20, 'famePoints', 'DESC', 'Kap');
    }

    public function testGetShipsRankingReturnsEmptyWhenNoShips(): void
    {
        $qb = $this->makeQueryBuilderMock();
        $query = $this->createMock(Query::class);
        $query->method('getResult')->willReturn([]);
        $qb->method('getQuery')->willReturn($query);

        $shipRepo = $this->createMock(ShipRepository::class);
        $shipRepo->method('createQueryBuilder')->willReturn($qb);

        $service = new RankingService(
            $this->createMock(UserRepository::class),
            $shipRepo,
            $this->createMock(ShipMemberRepository::class),
            $this->createMock(TitleService::class),
        );

        $response = $service->getShipsRanking(1, 10, 'totalFamePoints', 'DESC');
        self::assertSame([], $response->items);
        self::assertSame(0, $response->pagination->total);
    }

    public function testInvalidSortOrderNormalizesToDesc(): void
    {
        $qbCount = $this->makeQueryBuilderMock();
        $queryCount = $this->createMock(Query::class);
        $queryCount->method('getSingleScalarResult')->willReturn(0);
        $qbCount->method('getQuery')->willReturn($queryCount);

        $qbList = $this->makeQueryBuilderMock();
        $orderSpy = $this->createMock(QueryBuilder::class);
        $orderSpy->expects(self::atLeastOnce())->method('orderBy')->willReturnSelf();
        $orderSpy->method('addOrderBy')->willReturnSelf();
        $orderSpy->method('leftJoin')->willReturnSelf();
        $orderSpy->method('addSelect')->willReturnSelf();
        $orderSpy->method('andWhere')->willReturnSelf();
        $orderSpy->method('setFirstResult')->willReturnSelf();
        $orderSpy->method('setMaxResults')->willReturnSelf();

        $queryList = $this->createMock(Query::class);
        $queryList->method('getResult')->willReturn([]);
        $orderSpy->method('getQuery')->willReturn($queryList);

        $i = 0;
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('createQueryBuilder')->willReturnCallback(static function () use (&$i, $qbCount, $orderSpy) {
            ++$i;

            return $i === 1 ? $qbCount : $orderSpy;
        });

        $service = new RankingService(
            $userRepo,
            $this->createMock(ShipRepository::class),
            $this->createMock(ShipMemberRepository::class),
            $this->createMock(TitleService::class),
        );

        $service->getPlayersRanking(1, 20, 'famePoints', 'not-a-direction');
    }

    private function makeQueryBuilderMock(): QueryBuilder
    {
        $qb = $this->createMock(QueryBuilder::class);
        foreach (['select', 'andWhere', 'leftJoin', 'addSelect', 'orderBy', 'addOrderBy', 'setFirstResult', 'setMaxResults'] as $m) {
            $qb->method($m)->willReturnSelf();
        }

        return $qb;
    }
}
