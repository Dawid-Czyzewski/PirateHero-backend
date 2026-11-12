<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Application;

use App\Character\Application\Query\GetMyCharacter\GetMyCharacterHandler;
use App\Character\Application\Query\GetMyCharacter\GetMyCharacterQuery;
use App\Character\Domain\Exception\CharacterNotFound;
use App\Character\Domain\Model\Appearance;
use App\Character\Domain\Model\Character;
use App\Character\Domain\Model\CharacterId;
use App\Shared\Domain\Model\UserId;
use App\Tests\Double\Character\InMemoryCharacterRepository;
use App\Tests\Double\Character\InMemoryPlayerNicks;
use PHPUnit\Framework\TestCase;

final class GetMyCharacterHandlerTest extends TestCase
{
    private const USER_ID = '01a1a31d-3df0-7194-80d3-f04533ffb060';
    private const CHARACTER_ID = '01a1a3a0-0000-7000-8000-000000000001';

    public function testReturnsViewOfUsersCharacter(): void
    {
        $characters = new InMemoryCharacterRepository();
        $characters->save(Character::create(
            CharacterId::fromString(self::CHARACTER_ID),
            UserId::fromString(self::USER_ID),
            new Appearance(face: 3, hair: 1, beard: 7, skin: 4, tattoo: 0),
        ));

        $view = (new GetMyCharacterHandler($characters, self::nicks()))(new GetMyCharacterQuery(self::USER_ID));

        self::assertSame(self::CHARACTER_ID, $view->id);
        self::assertSame('Cebulak', $view->nick);
        self::assertSame(1, $view->level);
        self::assertSame(15, $view->maxHp);
        self::assertSame(['strength' => 1, 'endurance' => 1, 'technique' => 1, 'charisma' => 1], $view->stats);
        self::assertSame(['cebuliony' => 100, 'zloteZeby' => 10, 'kondycha' => 100, 'zasiegi' => 0, 'medale' => 0], $view->wallet);
        self::assertSame(['face' => 3, 'hair' => 1, 'beard' => 7, 'skin' => 4, 'tattoo' => 0], $view->appearance);
    }

    public function testThrowsWhenUserHasNoCharacter(): void
    {
        $this->expectException(CharacterNotFound::class);

        (new GetMyCharacterHandler(new InMemoryCharacterRepository(), self::nicks()))(new GetMyCharacterQuery(self::USER_ID));
    }

    private static function nicks(): InMemoryPlayerNicks
    {
        return new InMemoryPlayerNicks([self::USER_ID => 'Cebulak']);
    }
}
