<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Combat;

use App\Entity\User;
use App\Enum\FightMoveResult;
use App\Service\Combat\ShipsFightSerializer;
use PHPUnit\Framework\TestCase;

final class ShipsFightSerializerTest extends TestCase
{
    public function testFormatMoveReturnsExpectedKeys(): void
    {
        $player = $this->createConfiguredMock(User::class, ['getId' => '1', 'getUsername' => 'atk']);
        $target = $this->createConfiguredMock(User::class, ['getId' => '2', 'getUsername' => 'def']);

        $move = $this->createMock(\App\Entity\ShipsFightMove::class);
        $move->method('getMoveNumber')->willReturn(1);
        $move->method('getPlayer')->willReturn($player);
        $move->method('getTarget')->willReturn($target);
        $move->method('getResult')->willReturn(FightMoveResult::HIT);
        $move->method('getDamage')->willReturn(42);
        $move->method('getTargetHealthAfter')->willReturn(8);

        $payload = (new ShipsFightSerializer())->formatMove($move, true, 90, 100, 80);

        foreach ([
            'moveNumber',
            'player',
            'target',
            'result',
            'damage',
            'targetHealthAfter',
            'playerHealthAfter',
            'attackerHealth',
            'defenderHealth',
            'isAttackerSide',
        ] as $key) {
            self::assertArrayHasKey($key, $payload);
        }

        self::assertSame('HIT', $payload['result']);
        self::assertTrue($payload['isAttackerSide']);
    }
}
