<?php

declare(strict_types=1);

namespace App\Character\Domain\Model;

use App\Character\Domain\Exception\InsufficientStamina;
use App\Character\Domain\Exception\InvalidCharacterData;

final readonly class Stamina
{
    public const MAX = 100;
    public const SECONDS_PER_POINT = 180;
    public const MIN_COST = 1;

    public function __construct(private int $points, private \DateTimeImmutable $updatedAt)
    {
        if ($points < 0 || $points > self::MAX) {
            throw InvalidCharacterData::outOfRange('stamina', $points, 0, self::MAX);
        }
    }

    public static function full(\DateTimeImmutable $now): self
    {
        return new self(self::MAX, $now);
    }

    public function points(): int
    {
        return $this->points;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function current(\DateTimeImmutable $now): int
    {
        return $this->regenerated($now)->points;
    }

    public function secondsToNextPoint(\DateTimeImmutable $now): ?int
    {
        $regenerated = $this->regenerated($now);
        if ($regenerated->isFull()) {
            return null;
        }

        return self::SECONDS_PER_POINT - self::secondsBetween($regenerated->updatedAt, $now);
    }

    public function spend(int $cost, \DateTimeImmutable $now): self
    {
        if ($cost < self::MIN_COST) {
            throw InvalidCharacterData::belowMinimum('staminaCost', $cost, self::MIN_COST);
        }

        $regenerated = $this->regenerated($now);
        if ($regenerated->points < $cost) {
            throw InsufficientStamina::forCost($cost, $regenerated->points);
        }

        return new self($regenerated->points - $cost, $regenerated->isFull() ? $now : $regenerated->updatedAt);
    }

    private function regenerated(\DateTimeImmutable $now): self
    {
        $gained = intdiv(self::secondsBetween($this->updatedAt, $now), self::SECONDS_PER_POINT);
        if (0 === $gained || $this->isFull()) {
            return $this;
        }

        return new self(
            min(self::MAX, $this->points + $gained),
            $this->updatedAt->add(new \DateInterval(\sprintf('PT%dS', $gained * self::SECONDS_PER_POINT))),
        );
    }

    private function isFull(): bool
    {
        return $this->points >= self::MAX;
    }

    private static function secondsBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return max(0, $to->getTimestamp() - $from->getTimestamp());
    }
}
