<?php

declare(strict_types=1);

namespace App\Tests\Unit\Character\Presentation;

use App\Character\Domain\Model\Stat;
use App\Character\Presentation\Api\TrainStatInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class TrainStatInputTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function knownStats(): iterable
    {
        foreach (Stat::values() as $value) {
            yield $value => [$value];
        }
    }

    #[DataProvider('knownStats')]
    public function testAcceptsEveryStatAndMapsItToTheCommand(string $value): void
    {
        $input = self::input($value);

        self::assertCount(0, $this->validator->validate($input));
        self::assertSame(Stat::from($value), $input->toCommand('user-1')->stat);
    }

    /**
     * @return iterable<string, array{string|null}>
     */
    public static function invalidStats(): iterable
    {
        yield 'unknown' => ['speed'];
        yield 'old name of energy' => ['stamina'];
        yield 'empty' => [''];
        yield 'missing' => [null];
    }

    #[DataProvider('invalidStats')]
    public function testRejectsUnknownOrMissingStat(?string $value): void
    {
        $violations = $this->validator->validate(self::input($value));

        self::assertGreaterThan(0, \count($violations));
        self::assertSame('stat', $violations->get(0)->getPropertyPath());
    }

    private static function input(?string $stat): TrainStatInput
    {
        $input = new TrainStatInput();
        $input->stat = $stat;

        return $input;
    }
}
