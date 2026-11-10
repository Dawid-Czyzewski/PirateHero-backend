<?php

declare(strict_types=1);

namespace App\Character\Application\Query\GetMyCharacter;

final readonly class GetMyCharacterQuery
{
    public function __construct(public string $userId)
    {
    }
}
