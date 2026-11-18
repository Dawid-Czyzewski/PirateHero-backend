<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Presentation;

use ApiPlatform\Metadata\Get;
use App\Character\Application\Query\GetMyCharacter\GetMyCharacterHandler;
use App\Character\Domain\Model\Appearance;
use App\Character\Domain\Model\Character;
use App\Character\Domain\Model\CharacterId;
use App\Character\Presentation\Api\State\MyCharacterProvider;
use App\Shared\Domain\Model\UserId;
use App\Tests\Double\Character\InMemoryCharacterRepository;
use App\Tests\Double\Character\InMemoryPlayerNicks;
use App\Tests\Double\Shared\FixedCurrentUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class MyCharacterProviderTest extends TestCase
{
    private const USER_ID = '01a1a31d-3df0-7194-80d3-f04533ffb060';
    private const CHARACTER_ID = '01a1a3a0-0000-7000-8000-000000000001';

    public function testReturnsCharacterOfCurrentUser(): void
    {
        $characters = new InMemoryCharacterRepository();
        $characters->save(Character::create(
            CharacterId::fromString(self::CHARACTER_ID),
            UserId::fromString(self::USER_ID),
            new Appearance(face: 3, hair: 1, beard: 7, skin: 4, tattoo: 0),
        ));

        $resource = $this->provider($characters, self::USER_ID)->provide(new Get());

        self::assertSame(self::CHARACTER_ID, $resource->id);
        self::assertSame('Cebulak', $resource->nick);
        self::assertSame(7, $resource->appearance->beard);
        self::assertSame(10, $resource->wallet->goldTeeth);
    }

    public function testMapsMissingCharacterToNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Character not found.');

        $this->provider(new InMemoryCharacterRepository(), self::USER_ID)->provide(new Get());
    }

    public function testDeniesAnonymousUser(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->provider(new InMemoryCharacterRepository(), null)->provide(new Get());
    }

    private function provider(InMemoryCharacterRepository $characters, ?string $userId): MyCharacterProvider
    {
        return new MyCharacterProvider(
            new FixedCurrentUser(null === $userId ? null : UserId::fromString($userId)),
            new GetMyCharacterHandler($characters, new InMemoryPlayerNicks([self::USER_ID => 'Cebulak'])),
        );
    }
}
