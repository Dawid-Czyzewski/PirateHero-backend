<?php

declare(strict_types=1);

namespace App\Tests\Unit\Dto;

use App\Dto\UserRegisterDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class UserRegisterDtoValidationTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidPayloadPassesValidation(): void
    {
        $dto = $this->validDto();
        $violations = $this->validator->validate($dto);

        self::assertCount(0, $violations);
    }

    public function testAvatarNameIsRequired(): void
    {
        $dto = $this->validDto();
        $dto->avatarName = '';

        $violations = $this->validator->validate($dto);
        self::assertGreaterThan(0, $violations->count());

        $paths = [];
        foreach ($violations as $violation) {
            $paths[] = $violation->getPropertyPath();
        }

        self::assertContains('avatarName', $paths);
    }

    public function testAvatarNameFormatValidation(): void
    {
        $dto = $this->validDto();
        $dto->avatarName = 'Captain Blackbeard';

        $violations = $this->validator->validate($dto);

        $messages = [];
        foreach ($violations as $violation) {
            $messages[] = $violation->getMessage();
        }

        self::assertContains('Avatar name can only contain lowercase letters, numbers, underscores and hyphens.', $messages);
    }

    private function validDto(): UserRegisterDto
    {
        $dto = new UserRegisterDto();
        $dto->email = 'captain@test.local';
        $dto->username = 'captain_1';
        $dto->password = 'Test_SecurePass_9';
        $dto->passwordRepeat = 'Test_SecurePass_9';
        $dto->rulesAccepted = true;
        $dto->avatarName = 'captain';

        return $dto;
    }
}
