<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Dto\Api\Ranking\PaginationDto;
use App\Dto\Api\Ranking\PlayerRankingEntryDto;
use App\Dto\Api\Ranking\PlayersRankingResponse;
use App\Dto\Api\Ranking\ShipRankingEntryDto;
use App\Dto\Api\Ranking\ShipsRankingResponse;
use App\Enum\ShipRole;
use App\Repository\ShipMemberRepository;
use App\Repository\ShipRepository;
use App\Repository\UserRepository;
use App\Service\Progression\TitleService;

class RankingService
{
    public function __construct(
        private UserRepository $userRepository,
        private ShipRepository $shipRepository,
        private ShipMemberRepository $shipMemberRepository,
        private TitleService $titleService,
    ) {
    }

    public function getPlayersRanking(
        int $page,
        int $limit,
        string $sortBy,
        string $sortOrder,
        ?string $search = null,
    ): PlayersRankingResponse {
        $offset = ($page - 1) * $limit;

        if (!in_array(strtoupper($sortOrder), ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        } else {
            $sortOrder = strtoupper($sortOrder);
        }

        $searchTerm = $this->normalizeSearchTerm($search);

        $countQb = $this->userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.activateToken IS NULL');
        $this->applyPlayersSearchFilter($countQb, $searchTerm);
        $totalCount = (int) $countQb->getQuery()->getSingleScalarResult();

        $qb = $this->userRepository->createQueryBuilder('u')
            ->leftJoin('u.level', 'l')
            ->leftJoin('u.equippedTitle', 'et')
            ->addSelect('l', 'et')
            ->andWhere('u.activateToken IS NULL');
        $this->applyPlayersSearchFilter($qb, $searchTerm);

        switch ($sortBy) {
            case 'username':
                $qb->orderBy('u.username', $sortOrder);
                break;
            case 'level':
                $qb->orderBy('l.name', $sortOrder);
                break;
            case 'famePoints':
            default:
                $qb->orderBy('u.famePoints', $sortOrder);
                if ($sortBy === 'famePoints') {
                    $qb->addOrderBy('u.experiencePoints', 'DESC');
                }
                break;
        }

        $users = $qb
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $items = [];
        foreach ($users as $user) {
            $shipMember = $this->shipMemberRepository->findOneBy(['user' => $user]);
            $ship = $shipMember ? $shipMember->getShip() : null;

            $items[] = new PlayerRankingEntryDto(
                id: (string) $user->getId(),
                username: (string) $user->getUsername(),
                famePoints: (int) ($user->getFamePoints() ?? 0),
                experiencePoints: (int) $user->getExperiencePoints(),
                level: $user->getLevel() ? [
                    'id' => $user->getLevel()->getId(),
                    'name' => $user->getLevel()->getName(),
                ] : null,
                ship: $ship ? [
                    'id' => $ship->getId(),
                    'title' => $ship->getTitle(),
                ] : null,
                equippedTitle: $this->titleService->buildEquippedTitleDto($user->getEquippedTitle()),
            );
        }

        $pagination = new PaginationDto(
            page: $page,
            limit: $limit,
            total: $totalCount,
            totalPages: (int) ceil($totalCount / $limit),
        );

        return new PlayersRankingResponse($items, $pagination);
    }

    public function getShipsRanking(
        int $page,
        int $limit,
        string $sortBy,
        string $sortOrder,
        ?string $search = null,
    ): ShipsRankingResponse {
        $offset = ($page - 1) * $limit;

        if (!in_array(strtoupper($sortOrder), ['ASC', 'DESC'])) {
            $sortOrder = 'DESC';
        } else {
            $sortOrder = strtoupper($sortOrder);
        }

        $searchTerm = $this->normalizeSearchTerm($search);

        $ships = $this->shipRepository->createQueryBuilder('c')
            ->leftJoin('c.members', 'cm')
            ->leftJoin('cm.user', 'u')
            ->addSelect('cm', 'u')
            ->getQuery()
            ->getResult();

        $shipRankings = [];
        foreach ($ships as $ship) {
            $members = $ship->getMembers();
            $totalFamePoints = (int) $ship->getFamePoints();
            $memberIds = [];
            $activeMemberCount = 0;

            foreach ($members as $member) {
                $user = $member->getUser();

                if ($user && $user->getActivateToken() === null) {
                    $memberIds[] = $user->getId();
                    ++$activeMemberCount;
                }
            }

            if ($activeMemberCount > 0) {
                $captainUsername = null;
                foreach ($members as $member) {
                    if ($member->getRole() !== ShipRole::OWNER) {
                        continue;
                    }
                    $captainUser = $member->getUser();
                    if ($captainUser !== null && $captainUser->getActivateToken() === null) {
                        $captainUsername = $captainUser->getUsername();
                    }
                    break;
                }

                $shipRankings[] = [
                    'id' => $ship->getId(),
                    'title' => $ship->getTitle(),
                    'totalFamePoints' => $totalFamePoints,
                    'memberCount' => $activeMemberCount,
                    'memberIds' => $memberIds,
                    'requiresInvitation' => $ship->getRequiresInvitation(),
                    'maxMembers' => $ship->getMaxMembers(),
                    'captainUsername' => $captainUsername,
                ];
            }
        }

        usort($shipRankings, static function ($a, $b) use ($sortBy, $sortOrder) {
            $result = 0;
            switch ($sortBy) {
                case 'title':
                    $result = strcmp($a['title'], $b['title']);
                    break;
                case 'memberCount':
                    $result = $a['memberCount'] <=> $b['memberCount'];
                    break;
                case 'totalFamePoints':
                default:
                    $result = $a['totalFamePoints'] <=> $b['totalFamePoints'];
                    break;
            }

            return $sortOrder === 'ASC' ? $result : -$result;
        });

        if ($searchTerm !== null) {
            $shipRankings = array_values(array_filter(
                $shipRankings,
                static fn (array $row): bool => str_contains(mb_strtolower((string) $row['title']), $searchTerm)
            ));
        }

        $totalCount = count($shipRankings);
        $paginated = array_slice($shipRankings, $offset, $limit);

        $items = [];
        foreach ($paginated as $row) {
            $items[] = new ShipRankingEntryDto(
                id: (string) $row['id'],
                title: (string) $row['title'],
                totalFamePoints: (int) $row['totalFamePoints'],
                memberCount: (int) $row['memberCount'],
                memberIds: array_map(static fn ($id) => (string) $id, $row['memberIds']),
                requiresInvitation: (bool) $row['requiresInvitation'],
                maxMembers: (int) $row['maxMembers'],
                captainUsername: $row['captainUsername'] ?? null,
            );
        }

        $pagination = new PaginationDto(
            page: $page,
            limit: $limit,
            total: $totalCount,
            totalPages: (int) ceil($totalCount / $limit),
        );

        return new ShipsRankingResponse($items, $pagination);
    }

    private function normalizeSearchTerm(?string $search): ?string
    {
        if ($search === null) {
            return null;
        }
        $trimmed = trim($search);
        if ($trimmed === '') {
            return null;
        }

        return mb_strtolower($trimmed);
    }

    private function applyPlayersSearchFilter(\Doctrine\ORM\QueryBuilder $qb, ?string $searchTerm): void
    {
        if ($searchTerm === null) {
            return;
        }

        $qb->andWhere('LOWER(u.username) LIKE :rankingSearch')
            ->setParameter('rankingSearch', '%'.$searchTerm.'%');
    }
}
