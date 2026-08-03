<?php

declare(strict_types=1);

namespace App\Dto\Ship;

use Symfony\Component\Validator\Constraints as Assert;

final class RequestIdInput
{
    #[Assert\NotNull(message: 'requestId is required')]
    #[Assert\Positive]
    public ?int $requestId = null;
}
