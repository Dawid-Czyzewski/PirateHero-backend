<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Presentation;

use App\Character\Presentation\Api\CreateCharacterInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateCharacterInputTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testAcceptsLowestAndHighestVariants(): void
    {
        self::assertCount(0, $this->validator->validate(self::input(0, 0, 0, 0, 0)));
        self::assertCount(0, $this->validator->validate(self::input(7, 7, 7, 4, 7)));
    }

    /**
     * @return iterable<string, array{CreateCharacterInput, list<string>}>
     */
    public static function invalidInputs(): iterable
    {
        yield 'face 8' => [self::input(8, 0, 0, 0, 0), ['face']];
        yield 'hair negative' => [self::input(0, -1, 0, 0, 0), ['hair']];
        yield 'beard 8' => [self::input(0, 0, 8, 0, 0), ['beard']];
        yield 'skin 5' => [self::input(0, 0, 0, 5, 0), ['skin']];
        yield 'tattoo 8' => [self::input(0, 0, 0, 0, 8), ['tattoo']];
        yield 'all missing' => [new CreateCharacterInput(), ['face', 'hair', 'beard', 'skin', 'tattoo']];
    }

    /**
     * @param list<string> $fields
     */
    #[DataProvider('invalidInputs')]
    public function testReportsEveryInvalidField(CreateCharacterInput $input, array $fields): void
    {
        $violated = [];
        foreach ($this->validator->validate($input) as $violation) {
            $violated[] = $violation->getPropertyPath();
        }

        self::assertSame($fields, $violated);
    }

    public function testBuildsCommandForUser(): void
    {
        $command = self::input(3, 1, 7, 4, 0)->toCommand('01a1a31d-3df0-7194-80d3-f04533ffb060');

        self::assertSame('01a1a31d-3df0-7194-80d3-f04533ffb060', $command->userId);
        self::assertSame([3, 1, 7, 4, 0], [$command->face, $command->hair, $command->beard, $command->skin, $command->tattoo]);
    }

    public function testRefusesToBuildCommandFromIncompleteInput(): void
    {
        $this->expectException(\LogicException::class);

        (new CreateCharacterInput())->toCommand('01a1a31d-3df0-7194-80d3-f04533ffb060');
    }

    private static function input(int $face, int $hair, int $beard, int $skin, int $tattoo): CreateCharacterInput
    {
        $input = new CreateCharacterInput();
        $input->face = $face;
        $input->hair = $hair;
        $input->beard = $beard;
        $input->skin = $skin;
        $input->tattoo = $tattoo;

        return $input;
    }
}
