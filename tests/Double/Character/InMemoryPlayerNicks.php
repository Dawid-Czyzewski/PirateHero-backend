<?php

declare(strict_types=1);

namespace App\Tests\Double\Character;

use App\Character\Application\Player\PlayerNicks;
use App\Shared\Domain\Model\UserId;

final readonly class InMemoryPlayerNicks implements PlayerNicks
{
    /**
     * @param array<string, string> $nicks
     */
    public function __construct(private array $nicks)
    {
    }

    public function nickOf(UserId $userId): string
    {
        return $this->nicks[$userId->toString()] ?? throw new \OutOfBoundsException(\sprintf('No nick for user "%s".', $userId->toString()));
    }
}
