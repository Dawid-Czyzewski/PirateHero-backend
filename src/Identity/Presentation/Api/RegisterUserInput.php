<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Api;

use App\Identity\Domain\Model\Email;
use App\Identity\Domain\Model\Nick;
use App\Identity\Domain\Model\PlainPassword;
use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: Email::MAX_LENGTH)]
    public string $email = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: Nick::MIN_LENGTH, max: Nick::MAX_LENGTH)]
    public string $nick = '';

    #[Assert\NotBlank]
    #[Assert\Length(min: PlainPassword::MIN_LENGTH, max: PlainPassword::MAX_LENGTH)]
    public string $password = '';
}
