<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto\Api\Ranking;

use App\Dto\Api\Ranking\ShipRankingEntryDto;
use PHPUnit\Framework\TestCase;

final class ShipRankingEntryDtoTest extends TestCase
{
    public function testToArrayIncludesCaptainAndMaxMembers(): void
    {
        $dto = new ShipRankingEntryDto(
            id: '12',
            title: 'Test Crew',
            totalFamePoints: 100,
            memberCount: 3,
            memberIds: ['1', '2'],
            requiresInvitation: true,
            maxMembers: 10,
            captainUsername: 'captain_joe',
        );

        self::assertSame(
            [
                'id' => '12',
                'title' => 'Test Crew',
                'totalFamePoints' => 100,
                'memberCount' => 3,
                'memberIds' => ['1', '2'],
                'requiresInvitation' => true,
                'maxMembers' => 10,
                'captainUsername' => 'captain_joe',
            ],
            $dto->toArray(),
        );
    }

    public function testToArrayAllowsNullCaptain(): void
    {
        $dto = new ShipRankingEntryDto(
            id: '1',
            title: 'Solo',
            totalFamePoints: 0,
            memberCount: 0,
            memberIds: [],
            requiresInvitation: false,
            maxMembers: 6,
            captainUsername: null,
        );

        self::assertNull($dto->toArray()['captainUsername']);
    }
}
