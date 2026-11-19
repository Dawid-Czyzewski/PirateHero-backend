<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Presentation;

use ApiPlatform\Metadata\Post;
use App\Character\Application\Command\TrainStat\TrainStatHandler;
use App\Character\Application\Query\GetMyCharacter\GetMyCharacterHandler;
use App\Character\Domain\Model\Appearance;
use App\Character\Domain\Model\Character;
use App\Character\Domain\Model\CharacterId;
use App\Character\Presentation\Api\State\TrainStatProcessor;
use App\Character\Presentation\Api\TrainStatInput;
use App\Shared\Domain\Model\UserId;
use App\Tests\Double\Character\InMemoryCharacterRepository;
use App\Tests\Double\Character\InMemoryPlayerNicks;
use App\Tests\Double\Shared\FixedCurrentUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class TrainStatProcessorTest extends TestCase
{
    private const USER_ID = '01a1a31d-3df0-7194-80d3-f04533ffb060';

    private InMemoryCharacterRepository $characters;
    private MockClock $clock;

    protected function setUp(): void
    {
        $this->characters = new InMemoryCharacterRepository();
        $this->clock = new MockClock(new \DateTimeImmutable('2026-11-19 12:00:00'));
    }

    public function testReturnsTheUpdatedCharacter(): void
    {
        $this->characters->save($this->character());

        $resource = $this->processor(self::USER_ID)->process(self::input('endurance'), new Post());

        self::assertSame(2, $resource->stats->endurance);
        self::assertSame(25, $resource->maxHp);
        self::assertSame(90, $resource->stamina->current);
        self::assertSame(180, $resource->stamina->nextPointInSeconds);
    }

    public function testMapsMissingStaminaToConflictWithPolishMessage(): void
    {
        $character = $this->character();
        $character->spendStamina(95, $this->clock->now());
        $this->characters->save($character);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Za mało Kondychy na trening: potrzeba 10, masz 5.');

        $this->processor(self::USER_ID)->process(self::input('strength'), new Post());
    }

    public function testMapsMissingCharacterToNotFound(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $this->processor(self::USER_ID)->process(self::input('strength'), new Post());
    }

    public function testDeniesAnonymousUser(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->processor(null)->process(self::input('strength'), new Post());
    }

    private function processor(?string $userId): TrainStatProcessor
    {
        return new TrainStatProcessor(
            new FixedCurrentUser(null === $userId ? null : UserId::fromString($userId)),
            new TrainStatHandler($this->characters, $this->clock),
            new GetMyCharacterHandler($this->characters, new InMemoryPlayerNicks([self::USER_ID => 'Cebulak']), $this->clock),
        );
    }

    private function character(): Character
    {
        return Character::create(
            CharacterId::fromString('01a1a3a0-0000-7000-8000-000000000001'),
            UserId::fromString(self::USER_ID),
            new Appearance(0, 0, 0, 0, 0),
            $this->clock->now(),
        );
    }

    private static function input(string $stat): TrainStatInput
    {
        $input = new TrainStatInput();
        $input->stat = $stat;

        return $input;
    }
}
