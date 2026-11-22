<?php

declare(strict_types=1);

namespace App\Combat\Domain\Model;

final readonly class FightLog
{
    /**
     * @param list<Round> $rounds
     */
    public function __construct(private array $rounds)
    {
    }

    /**
     * @return list<Round>
     */
    public function rounds(): array
    {
        return $this->rounds;
    }

    public function count(): int
    {
        return \count($this->rounds);
    }

    /**
     * @return list<array{number: int, attacker: string, damage: int, playerHp: int, npcHp: int}>
     */
    public function toArray(): array
    {
        return array_map(static fn (Round $round): array => $round->toArray(), $this->rounds);
    }
}
