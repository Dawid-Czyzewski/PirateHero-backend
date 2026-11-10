<?php

declare(strict_types=1);

namespace App\Tests\Unit\Identity\Presentation;

use App\Identity\Presentation\Api\RegisterUserInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RegisterUserInputTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();
    }

    public function testValidInputHasNoViolations(): void
    {
        self::assertCount(0, $this->validator->validate($this->input('gracz@example.com', 'Cebulak', 'tajnehaslo')));
    }

    /**
     * @return iterable<string, array{string, string, string, list<string>}>
     */
    public static function invalidInputs(): iterable
    {
        yield 'invalid email' => ['nie-email', 'Cebulak', 'tajnehaslo', ['email']];
        yield 'nick 2 chars' => ['gracz@example.com', 'ab', 'tajnehaslo', ['nick']];
        yield 'nick 17 chars' => ['gracz@example.com', str_repeat('a', 17), 'tajnehaslo', ['nick']];
        yield 'password 7 chars' => ['gracz@example.com', 'Cebulak', '1234567', ['password']];
        yield 'everything empty' => ['', '', '', ['email', 'nick', 'password']];
    }

    /**
     * @param list<string> $fields
     */
    #[DataProvider('invalidInputs')]
    public function testReportsEveryInvalidField(string $email, string $nick, string $password, array $fields): void
    {
        $violated = [];
        foreach ($this->validator->validate($this->input($email, $nick, $password)) as $violation) {
            $violated[] = $violation->getPropertyPath();
        }

        self::assertSame($fields, array_values(array_unique($violated)));
    }

    private function input(string $email, string $nick, string $password): RegisterUserInput
    {
        $input = new RegisterUserInput();
        $input->email = $email;
        $input->nick = $nick;
        $input->password = $password;

        return $input;
    }
}
