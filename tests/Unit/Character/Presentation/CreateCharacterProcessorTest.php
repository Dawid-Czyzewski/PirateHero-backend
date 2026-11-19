<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Presentation;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Validator\Exception\ValidationException;
use App\Character\Application\Command\CreateCharacter\CreateCharacterHandler;
use App\Character\Application\Query\GetMyCharacter\GetMyCharacterHandler;
use App\Character\Presentation\Api\CreateCharacterInput;
use App\Character\Presentation\Api\State\CreateCharacterProcessor;
use App\Shared\Domain\Model\UserId;
use App\Tests\Double\Character\InMemoryCharacterRepository;
use App\Tests\Double\Character\InMemoryPlayerNicks;
use App\Tests\Double\Shared\FixedCurrentUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class CreateCharacterProcessorTest extends TestCase
{
    private const USER_ID = '01a1a31d-3df0-7194-80d3-f04533ffb060';

    private InMemoryCharacterRepository $characters;

    protected function setUp(): void
    {
        $this->characters = new InMemoryCharacterRepository();
    }

    public function testReturnsCreatedCharacter(): void
    {
        $resource = $this->processor(self::USER_ID)->process($this->input(skin: 4), new Post());

        self::assertSame('Cebulak', $resource->nick);
        self::assertSame(1, $resource->level);
        self::assertSame(15, $resource->maxHp);
        self::assertSame(1, $resource->stats->endurance);
        self::assertSame(100, $resource->wallet->onions);
        self::assertSame(4, $resource->appearance->skin);
        self::assertSame(1, $this->characters->count());
    }

    public function testCreatesCharacterForCurrentUserOnly(): void
    {
        $this->processor(self::USER_ID)->process($this->input(), new Post());

        self::assertNotNull($this->characters->ofUserId(UserId::fromString(self::USER_ID)));
    }

    public function testMapsSecondCharacterToConflict(): void
    {
        $processor = $this->processor(self::USER_ID);
        $processor->process($this->input(), new Post());

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Character already exists.');

        $processor->process($this->input(), new Post());
    }

    public function testMapsDomainRuleViolationToValidationError(): void
    {
        try {
            $this->processor(self::USER_ID)->process($this->input(skin: 9), new Post());
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $violation = $e->getConstraintViolationList()->get(0);
            self::assertSame('skin', $violation->getPropertyPath());
            self::assertSame('Skin must be between 0 and 4, 9 given.', $violation->getMessage());
        }

        self::assertSame(0, $this->characters->count());
    }

    public function testDeniesAnonymousUser(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->processor(null)->process($this->input(), new Post());
    }

    private function processor(?string $userId): CreateCharacterProcessor
    {
        return new CreateCharacterProcessor(
            new FixedCurrentUser(null === $userId ? null : UserId::fromString($userId)),
            new CreateCharacterHandler($this->characters, new MockClock(new \DateTimeImmutable('2026-11-19 12:00:00'))),
            new GetMyCharacterHandler($this->characters, new InMemoryPlayerNicks([self::USER_ID => 'Cebulak']), new MockClock(new \DateTimeImmutable('2026-11-19 12:00:00'))),
        );
    }

    private function input(int $skin = 0): CreateCharacterInput
    {
        $input = new CreateCharacterInput();
        $input->face = 3;
        $input->hair = 1;
        $input->beard = 7;
        $input->skin = $skin;
        $input->tattoo = 0;

        return $input;
    }
}
