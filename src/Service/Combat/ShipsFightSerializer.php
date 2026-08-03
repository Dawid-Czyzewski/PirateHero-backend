<?php

declare(strict_types=1);

namespace App\Service\Combat;

use App\Entity\Ship;
use App\Entity\ShipsFight;
use App\Entity\ShipsFightMove;
use App\Enum\FightResult;
use App\Exception\OperationForbiddenException;
use App\Mapper\Api\FightMapper;

class ShipsFightSerializer
{
    public function formatMove(ShipsFightMove $move, bool $isAttackerSide, ?int $playerHealthAfter = null, ?int $attackerHealth = null, ?int $defenderHealth = null): array
    {
        return FightMapper::shipsMoveFromArray([
            'moveNumber' => $move->getMoveNumber(),
            'player' => [
                'id' => $move->getPlayer()->getId(),
                'username' => $move->getPlayer()->getUsername(),
            ],
            'target' => [
                'id' => $move->getTarget()->getId(),
                'username' => $move->getTarget()->getUsername(),
            ],
            'result' => $move->getResult()->value,
            'damage' => $move->getDamage(),
            'targetHealthAfter' => $move->getTargetHealthAfter(),
            'playerHealthAfter' => $playerHealthAfter,
            'attackerHealth' => $attackerHealth,
            'defenderHealth' => $defenderHealth,
            'isAttackerSide' => $isAttackerSide,
        ])->toArray();
    }

    /**
     * Replays stored moves and attaches total-HP pools for both sides at each step (same shape as {@see buildFightDetailsPayload}).
     *
     * @return list<array<string, mixed>>
     */
    public function serializeAggregatedMoves(ShipsFight $fight): array
    {
        $fightMembers = $fight->getFightMembers();
        $attackerFightMembers = [];
        $defenderFightMembers = [];

        foreach ($fightMembers as $fightMember) {
            $memberData = [
                'id' => $fightMember->getUser()->getId(),
                'username' => $fightMember->getUser()->getUsername(),
                'avatarName' => $fightMember->getUser()->getAvatarName(),
                'initialHealth' => $fightMember->getInitialHealth(),
                'currentHealth' => $fightMember->getCurrentHealth(),
                'isDefeated' => $fightMember->isDefeated(),
            ];

            if ($fightMember->isAttackerSide()) {
                $attackerFightMembers[] = $memberData;
            } else {
                $defenderFightMembers[] = $memberData;
            }
        }

        $moves = $fight->getFightMoves()->toArray();
        usort($moves, static function ($a, $b) {
            return $a->getMoveNumber() <=> $b->getMoveNumber();
        });

        $attackerHealthByUser = [];
        $defenderHealthByUser = [];

        foreach ($attackerFightMembers as $member) {
            $attackerHealthByUser[$member['id']] = $member['initialHealth'];
        }

        foreach ($defenderFightMembers as $member) {
            $defenderHealthByUser[$member['id']] = $member['initialHealth'];
        }

        $formattedMoves = [];
        foreach ($moves as $move) {
            $player = $move->getPlayer();
            $target = $move->getTarget();

            $isPlayerAttacker = false;
            foreach ($attackerFightMembers as $member) {
                if ($member['id'] == $player->getId()) {
                    $isPlayerAttacker = true;
                    break;
                }
            }

            if ($isPlayerAttacker) {
                $defenderHealthByUser[$target->getId()] = $move->getTargetHealthAfter();
            } else {
                $attackerHealthByUser[$target->getId()] = $move->getTargetHealthAfter();
            }

            $currentAttackerHealth = array_sum($attackerHealthByUser);
            $currentDefenderHealth = array_sum($defenderHealthByUser);

            $playerHealthAfter = $isPlayerAttacker
                ? $attackerHealthByUser[$player->getId()]
                : $defenderHealthByUser[$player->getId()];

            $formattedMoves[] = $this->formatMove(
                $move,
                $isPlayerAttacker,
                $playerHealthAfter,
                $currentAttackerHealth,
                $currentDefenderHealth
            );
        }

        return $formattedMoves;
    }

    public function buildFightDetailsPayload(ShipsFight $fight, Ship $viewerShip): array
    {
        $isAttacker = $fight->getAttackerShip()->getId() === $viewerShip->getId();
        $isDefender = $fight->getDefenderShip()->getId() === $viewerShip->getId();

        if (!$isAttacker && !$isDefender) {
            throw new OperationForbiddenException('shipFightNotParticipant');
        }

        $attackerShip = $fight->getAttackerShip();
        $defenderShip = $fight->getDefenderShip();

        $fightMembers = $fight->getFightMembers();
        $attackerFightMembers = [];
        $defenderFightMembers = [];
        $attackerInitialHealth = 0;
        $defenderInitialHealth = 0;

        foreach ($fightMembers as $fightMember) {
            $memberData = [
                'id' => $fightMember->getUser()->getId(),
                'username' => $fightMember->getUser()->getUsername(),
                'avatarName' => $fightMember->getUser()->getAvatarName(),
                'initialHealth' => $fightMember->getInitialHealth(),
                'currentHealth' => $fightMember->getCurrentHealth(),
                'isDefeated' => $fightMember->isDefeated(),
            ];

            if ($fightMember->isAttackerSide()) {
                $attackerFightMembers[] = $memberData;
                $attackerInitialHealth += $fightMember->getInitialHealth();
            } else {
                $defenderFightMembers[] = $memberData;
                $defenderInitialHealth += $fightMember->getInitialHealth();
            }
        }

        $formattedMoves = $this->serializeAggregatedMoves($fight);

        $attackerWon = $fight->getResult() === FightResult::ATTACKER_WON;
        $attackerFamePoints = $fight->getScore()->getAttackerScore();
        $defenderFamePoints = $fight->getScore()->getDefenderScore();

        $viewerIsAttacker = $fight->getAttackerShip()->getId() === $viewerShip->getId();
        $viewerWon = ($viewerIsAttacker && $attackerWon) || (!$viewerIsAttacker && !$attackerWon);
        $viewerFameChange = $viewerIsAttacker ? $attackerFamePoints : $defenderFamePoints;

        return FightMapper::shipsDetailsResponse([
            'result' => $viewerWon ? 'victory' : 'defeat',
            'attackerScore' => $attackerFamePoints,
            'defenderScore' => $defenderFamePoints,
            'viewerFameChange' => $viewerFameChange,
            'moves' => $formattedMoves,
            'attackerShip' => [
                'id' => $attackerShip->getId(),
                'title' => $attackerShip->getTitle(),
            ],
            'defenderShip' => [
                'id' => $defenderShip->getId(),
                'title' => $defenderShip->getTitle(),
            ],
            'attackerInitialHealth' => $attackerInitialHealth,
            'defenderInitialHealth' => $defenderInitialHealth,
            'attackerMembers' => array_map(static function ($member) {
                return [
                    'id' => $member['id'],
                    'username' => $member['username'],
                    'avatarName' => $member['avatarName'] ?? null,
                    'initialHealth' => $member['initialHealth'],
                ];
            }, $attackerFightMembers),
            'defenderMembers' => array_map(static function ($member) {
                return [
                    'id' => $member['id'],
                    'username' => $member['username'],
                    'avatarName' => $member['avatarName'] ?? null,
                    'initialHealth' => $member['initialHealth'],
                ];
            }, $defenderFightMembers),
        ])->toArray();
    }

    /**
     * @param list<ShipsFight> $fights
     *
     * @return list<array<string, mixed>>
     */
    public function buildHistoryItems(Ship $ship, array $fights): array
    {
        $history = [];
        foreach ($fights as $fight) {
            $isAttacker = $fight->getAttackerShip()->getId() === $ship->getId();
            $opponentShip = $isAttacker ? $fight->getDefenderShip() : $fight->getAttackerShip();

            $shipResult = 'defeat';
            if ($fight->getResult() === FightResult::ATTACKER_WON && $isAttacker) {
                $shipResult = 'victory';
            } elseif ($fight->getResult() === FightResult::DEFENDER_WON && !$isAttacker) {
                $shipResult = 'victory';
            }

            $fameChange = $isAttacker
                ? $fight->getScore()->getAttackerScore()
                : $fight->getScore()->getDefenderScore();

            $history[] = FightMapper::shipsHistoryItemFromArray([
                'id' => $fight->getId(),
                'yourShip' => [
                    'id' => $ship->getId(),
                    'title' => $ship->getTitle(),
                ],
                'opponentShip' => [
                    'id' => $opponentShip->getId(),
                    'title' => $opponentShip->getTitle(),
                ],
                'result' => $shipResult,
                'famePointsChange' => $fameChange,
                'date' => $fight->getCreatedAt()->format('Y-m-d H:i'),
                'wasAttacker' => $isAttacker,
            ])->toArray();
        }

        return $history;
    }
}
