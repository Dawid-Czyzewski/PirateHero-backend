<?php

declare(strict_types=1);

namespace App\Dto\Ship;

use Symfony\Component\Validator\Constraints as Assert;

final class SendShipMessageInput
{
    #[Assert\NotBlank(message: 'Message content is required')]
    public string $content = '';
}
