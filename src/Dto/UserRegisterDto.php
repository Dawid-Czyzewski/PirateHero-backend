<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class UserRegisterDto
{
    #[Assert\NotBlank(message: 'Email is required.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    public string $email;

    #[Assert\NotBlank(message: 'Username is required.')]
    #[Assert\Length(
        min: 3,
        max: 30,
        minMessage: 'Username must be at least {{ limit }} characters.',
        maxMessage: 'Username cannot be longer than {{ limit }} characters.'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9_.-]+$/',
        message: 'Username can only contain letters, numbers, and . _ - characters.'
    )]
    public string $username;

    #[Assert\NotBlank(message: 'Password is required.')]
    #[Assert\Length(
        min: 6,
        minMessage: 'Password must be at least {{ limit }} characters long.'
    )]
    public string $password;

    #[Assert\NotBlank(message: 'Please confirm your password.')]
    public string $passwordRepeat;

    #[Assert\IsTrue(message: 'You must accept the terms and privacy policy.')]
    public bool $rulesAccepted;

    #[Assert\NotBlank(message: 'Avatar name is required.')]
    #[Assert\Length(
        min: 2,
        max: 64,
        minMessage: 'Avatar name must be at least {{ limit }} characters.',
        maxMessage: 'Avatar name cannot be longer than {{ limit }} characters.'
    )]
    #[Assert\Regex(
        pattern: '/^[a-z0-9_-]+$/',
        message: 'Avatar name can only contain lowercase letters, numbers, underscores and hyphens.'
    )]
    public string $avatarName;

    #[Assert\Callback]
    public function validatePasswordRepeat(ExecutionContextInterface $context): void
    {
        if (!isset($this->password) || !isset($this->passwordRepeat)) {
            return;
        }

        if ($this->password !== $this->passwordRepeat) {
            $context->buildViolation('Passwords must match.')
                ->atPath('passwordRepeat')
                ->addViolation();
        }
    }
}
