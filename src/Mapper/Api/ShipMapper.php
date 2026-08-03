<?php

declare(strict_types=1);

namespace App\Mapper\Api;

use App\Dto\Api\Ship\MyShipDataResponse;
use App\Dto\Api\Ship\ShipMemberDto;
use App\Dto\Api\Ship\ShipMemberSelfDto;
use App\Dto\Api\Ship\ShipMessageDto;
use App\Dto\Api\Ship\ShipPreviewResponse;
use App\Dto\Api\Ship\ShipSummaryDto;

final readonly class ShipMapper
{
    /**
     * @param array<string, mixed> $data
     */
    public static function myShipData(array $data): MyShipDataResponse
    {
        $ship = $data['ship'];
        $member = $data['member'];

        return new MyShipDataResponse(
            shipUpgradePricing: $data['shipUpgradePricing'],
            ship: new ShipSummaryDto(
                id: (int) $ship['id'],
                title: (string) $ship['title'],
                description: $ship['description'],
                internalNotes: $ship['internalNotes'],
                createdAt: (string) $ship['createdAt'],
                gold: (int) $ship['gold'],
                diamonds: (int) $ship['diamonds'],
                skillsUpgrade: (int) $ship['skillsUpgrade'],
                workUpgrade: (int) $ship['workUpgrade'],
                missionsUpgrade: (int) $ship['missionsUpgrade'],
                hullUpgrade: (int) $ship['hullUpgrade'],
                maxMembers: (int) $ship['maxMembers'],
                requiresInvitation: (bool) $ship['requiresInvitation'],
                famePoints: (int) $ship['famePoints'],
            ),
            member: $member === null ? null : new ShipMemberSelfDto(
                id: (int) $member['id'],
                role: $member['role'],
                joinedAt: (string) $member['joinedAt'],
                goldContributed: (int) $member['goldContributed'],
                diamondsContributed: (int) $member['diamondsContributed'],
            ),
            members: array_map(self::memberFromArray(...), $data['members']),
            messages: array_map(self::messageFromArray(...), $data['messages']),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function preview(array $data): ShipPreviewResponse
    {
        return new ShipPreviewResponse(
            id: (int) $data['id'],
            title: (string) $data['title'],
            description: $data['description'],
            createdAt: (string) $data['createdAt'],
            skillsUpgrade: (int) $data['skillsUpgrade'],
            workUpgrade: (int) $data['workUpgrade'],
            missionsUpgrade: (int) $data['missionsUpgrade'],
            hullUpgrade: (int) $data['hullUpgrade'],
            maxMembers: (int) $data['maxMembers'],
            requiresInvitation: (bool) $data['requiresInvitation'],
            famePoints: (int) $data['famePoints'],
            members: array_map(self::memberFromArray(...), $data['members']),
            membersCount: (int) $data['membersCount'],
            hasPendingRequest: (bool) $data['hasPendingRequest'],
            isOwner: (bool) $data['isOwner'],
            isFull: (bool) $data['isFull'],
        );
    }

    /**
     * @param array<string, mixed> $member
     */
    public static function memberFromArray(array $member): ShipMemberDto
    {
        return new ShipMemberDto(
            id: (int) $member['id'],
            role: $member['role'],
            joinedAt: (string) $member['joinedAt'],
            goldContributed: (int) $member['goldContributed'],
            diamondsContributed: (int) $member['diamondsContributed'],
            user: $member['user'],
        );
    }

    /**
     * @param array<string, mixed> $message
     */
    public static function messageFromArray(array $message): ShipMessageDto
    {
        return new ShipMessageDto(
            id: (int) $message['id'],
            content: (string) $message['content'],
            createdAt: (string) $message['createdAt'],
            isSystem: (bool) $message['isSystem'],
            author: $message['author'] ?? null,
        );
    }
}
