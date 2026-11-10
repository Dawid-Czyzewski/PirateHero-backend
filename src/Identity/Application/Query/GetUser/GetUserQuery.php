<?php

declare(strict_types=1);

namespace App\Identity\Application\Query\GetUser;

final readonly class GetUserQuery
{
    public function __construct(public string $userId)
    {
    }
}
