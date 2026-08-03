<?php

declare(strict_types=1);

namespace App\Mapper\Api;

use App\Dto\Api\Fight\FightHistoryItemDto;
use App\Dto\Api\Fight\FightMoveDto;
use App\Dto\Api\Fight\FightReplayResponse;
use App\Dto\Api\Fight\FightStartResponse;
use App\Dto\Api\Fight\ShipsFightDetailsResponse;
use App\Dto\Api\Fight\ShipsFightHistoryItemDto;
use App\Dto\Api\Fight\ShipsFightMoveDto;

final readonly class FightMapper
{
    /**
     * @param array{
     *     moveNumber: int|string,
     *     player: array{id: int|string, username: string},
     *     result: string,
     *     damage: int|string,
     *     attackerHealthAfter: int|string,
     *     defenderHealthAfter: int|string
     * } $move
     */
    public static function moveFromArray(array $move): FightMoveDto
    {
        return new FightMoveDto(
            moveNumber: (int) $move['moveNumber'],
            player: $move['player'],
            result: (string) $move['result'],
            damage: (int) $move['damage'],
            attackerHealthAfter: (int) $move['attackerHealthAfter'],
            defenderHealthAfter: (int) $move['defenderHealthAfter'],
        );
    }

    /**
     * @param array{
     *     fightId: int|string,
     *     result: string,
     *     attackerScore: int|string,
     *     defenderScore: int|string,
     *     famePointsChange: int|string,
     *     duelPointsSpent: int|string,
     *     playerId: int|string,
     *     opponentId: int|string,
     *     attackerUsername: string,
     *     moves: list<array{
     *         moveNumber: int|string,
     *         player: array{id: int|string, username: string},
     *         result: string,
     *         damage: int|string,
     *         attackerHealthAfter: int|string,
     *         defenderHealthAfter: int|string
     *     }>,
     *     attackerStats: array<string, int>,
     *     defenderStats: array<string, int>,
     *     opponent: array{id: int|string, username: string, avatarName: string|null, famePoints: int|null},
     *     opponents: list<array{
     *         id: int|string,
     *         username: string,
     *         avatarName: string|null,
     *         famePoints: int|null,
     *         level: string,
     *         averageSkill: float,
     *         totalStats: array<string, int>
     *     }>
     * } $payload
     */
    public static function startResponse(array $payload): FightStartResponse
    {
        return new FightStartResponse(
            fightId: (int) $payload['fightId'],
            result: (string) $payload['result'],
            attackerScore: (int) $payload['attackerScore'],
            defenderScore: (int) $payload['defenderScore'],
            famePointsChange: (int) $payload['famePointsChange'],
            duelPointsSpent: (int) $payload['duelPointsSpent'],
            playerId: $payload['playerId'],
            opponentId: $payload['opponentId'],
            attackerUsername: (string) $payload['attackerUsername'],
            moves: array_map(self::moveFromArray(...), $payload['moves']),
            attackerStats: $payload['attackerStats'],
            defenderStats: $payload['defenderStats'],
            opponent: $payload['opponent'],
            opponents: $payload['opponents'],
        );
    }

    /**
     * @param array{
     *     id: int|string,
     *     opponent: array{id: int|string, username: string},
     *     result: string,
     *     famePointsChange: int|string,
     *     date: string,
     *     wasAttacker: bool
     * } $item
     */
    public static function historyItemFromArray(array $item): FightHistoryItemDto
    {
        return new FightHistoryItemDto(
            id: (int) $item['id'],
            opponent: $item['opponent'],
            result: (string) $item['result'],
            famePointsChange: (int) $item['famePointsChange'],
            date: (string) $item['date'],
            wasAttacker: (bool) $item['wasAttacker'],
        );
    }

    /**
     * @param array{
     *     fightId: int|string,
     *     viewerWasAttacker: bool,
     *     resultForViewer: string,
     *     famePointsChangeForViewer: int|string,
     *     attacker: array{id: int|string, username: string, avatarName: string|null},
     *     defender: array{id: int|string, username: string, avatarName: string|null},
     *     attackerMaxHp: int|string,
     *     defenderMaxHp: int|string,
     *     moves: list<array{
     *         moveNumber: int|string,
     *         player: array{id: int|string, username: string},
     *         result: string,
     *         damage: int|string,
     *         attackerHealthAfter: int|string,
     *         defenderHealthAfter: int|string
     *     }>
     * } $payload
     */
    public static function replayResponse(array $payload): FightReplayResponse
    {
        return new FightReplayResponse(
            fightId: (int) $payload['fightId'],
            viewerWasAttacker: (bool) $payload['viewerWasAttacker'],
            resultForViewer: (string) $payload['resultForViewer'],
            famePointsChangeForViewer: (int) $payload['famePointsChangeForViewer'],
            attacker: $payload['attacker'],
            defender: $payload['defender'],
            attackerMaxHp: (int) $payload['attackerMaxHp'],
            defenderMaxHp: (int) $payload['defenderMaxHp'],
            moves: array_map(self::moveFromArray(...), $payload['moves']),
        );
    }

    /**
     * @param array{
     *     moveNumber: int|string,
     *     player: array{id: int|string, username: string},
     *     target: array{id: int|string, username: string},
     *     result: string,
     *     damage: int|string,
     *     targetHealthAfter: int|string,
     *     playerHealthAfter: int|string|null,
     *     attackerHealth: int|string|null,
     *     defenderHealth: int|string|null,
     *     isAttackerSide: bool
     * } $move
     */
    public static function shipsMoveFromArray(array $move): ShipsFightMoveDto
    {
        return new ShipsFightMoveDto(
            moveNumber: (int) $move['moveNumber'],
            player: $move['player'],
            target: $move['target'],
            result: (string) $move['result'],
            damage: (int) $move['damage'],
            targetHealthAfter: (int) $move['targetHealthAfter'],
            playerHealthAfter: $move['playerHealthAfter'] !== null ? (int) $move['playerHealthAfter'] : null,
            attackerHealth: $move['attackerHealth'] !== null ? (int) $move['attackerHealth'] : null,
            defenderHealth: $move['defenderHealth'] !== null ? (int) $move['defenderHealth'] : null,
            isAttackerSide: (bool) $move['isAttackerSide'],
        );
    }

    /**
     * @param array{
     *     result: string,
     *     attackerScore: int|string,
     *     defenderScore: int|string,
     *     viewerFameChange: int|string,
     *     moves: list<array{
     *         moveNumber: int,
     *         player: array{id: int|string, username: string},
     *         target: array{id: int|string, username: string},
     *         result: string,
     *         damage: int,
     *         targetHealthAfter: int,
     *         playerHealthAfter: int|null,
     *         attackerHealth: int|null,
     *         defenderHealth: int|null,
     *         isAttackerSide: bool
     *     }>,
     *     attackerShip: array{id: int|string, title: string},
     *     defenderShip: array{id: int|string, title: string},
     *     attackerInitialHealth: int|string,
     *     defenderInitialHealth: int|string,
     *     attackerMembers: list<array{id: int|string, username: string, avatarName: string|null, initialHealth: int}>,
     *     defenderMembers: list<array{id: int|string, username: string, avatarName: string|null, initialHealth: int}>
     * } $payload
     */
    public static function shipsDetailsResponse(array $payload): ShipsFightDetailsResponse
    {
        return new ShipsFightDetailsResponse(
            result: (string) $payload['result'],
            attackerScore: (int) $payload['attackerScore'],
            defenderScore: (int) $payload['defenderScore'],
            viewerFameChange: (int) $payload['viewerFameChange'],
            moves: array_map(self::shipsMoveFromArray(...), $payload['moves']),
            attackerShip: $payload['attackerShip'],
            defenderShip: $payload['defenderShip'],
            attackerInitialHealth: (int) $payload['attackerInitialHealth'],
            defenderInitialHealth: (int) $payload['defenderInitialHealth'],
            attackerMembers: $payload['attackerMembers'],
            defenderMembers: $payload['defenderMembers'],
        );
    }

    /**
     * @param array{
     *     id: int|string,
     *     yourShip: array{id: int|string, title: string},
     *     opponentShip: array{id: int|string, title: string},
     *     result: string,
     *     famePointsChange: int|string,
     *     date: string,
     *     wasAttacker: bool
     * } $item
     */
    public static function shipsHistoryItemFromArray(array $item): ShipsFightHistoryItemDto
    {
        return new ShipsFightHistoryItemDto(
            id: (int) $item['id'],
            yourShip: $item['yourShip'],
            opponentShip: $item['opponentShip'],
            result: (string) $item['result'],
            famePointsChange: (int) $item['famePointsChange'],
            date: (string) $item['date'],
            wasAttacker: (bool) $item['wasAttacker'],
        );
    }
}
