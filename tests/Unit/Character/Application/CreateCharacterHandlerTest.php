<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Application;

use App\Character\Application\Command\CreateCharacter\CreateCharacterCommand;
use App\Character\Application\Command\CreateCharacter\CreateCharacterHandler;
use App\Character\Domain\Exception\CharacterAlreadyExists;
use App\Character\Domain\Exception\InvalidCharacterData;
use App\Character\Domain\Model\Character;
use App\Shared\Domain\Model\UserId;
use App\Tests\Double\Character\InMemoryCharacterRepository;
use PHPUnit\Framework\TestCase;

final class CreateCharacterHandlerTest extends TestCase
{
    private const USER_ID = '01a1a31d-3df0-7194-80d3-f04533ffb060';

    private InMemoryCharacterRepository $characters;
    private CreateCharacterHandler $handler;

    protected function setUp(): void
    {
        $this->characters = new InMemoryCharacterRepository();
        $this->handler = new CreateCharacterHandler($this->characters);
    }

    public function testCreatesCharacterForUserWithChosenAppearance(): void
    {
        $id = ($this->handler)(new CreateCharacterCommand(self::USER_ID, face: 3, hair: 1, beard: 7, skin: 4, tattoo: 0));

        $character = $this->characters->ofId($id);
        self::assertInstanceOf(Character::class, $character);
        self::assertTrue($character->userId()->equals(UserId::fromString(self::USER_ID)));
        self::assertSame(1, $character->level()->value());
        self::assertSame(15, $character->maxHp());
        self::assertSame([3, 1, 7, 4, 0], [
            $character->appearance()->face(),
            $character->appearance()->hair(),
            $character->appearance()->beard(),
            $character->appearance()->skin(),
            $character->appearance()->tattoo(),
        ]);
    }

    public function testRejectsSecondCharacterForSameUser(): void
    {
        ($this->handler)(new CreateCharacterCommand(self::USER_ID, 0, 0, 0, 0, 0));

        $this->expectException(CharacterAlreadyExists::class);

        ($this->handler)(new CreateCharacterCommand(self::USER_ID, 1, 1, 1, 1, 1));
    }

    public function testRejectsInvalidAppearanceWithoutSaving(): void
    {
        try {
            ($this->handler)(new CreateCharacterCommand(self::USER_ID, 0, 0, 0, 5, 0));
            self::fail('Expected InvalidCharacterData');
        } catch (InvalidCharacterData $e) {
            self::assertSame('skin', $e->field);
        }

        self::assertSame(0, $this->characters->count());
    }
}
