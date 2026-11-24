<?php

declare(strict_types=1);

namespace App\Character\Domain\Model;

use App\Character\Domain\Exception\InvalidCharacterData;

/**
 * @phpstan-consistent-constructor
 */
abstract readonly class RegeneratingResource
{
    public const MIN_COST = 1;

    final public function __construct(protected int $points, protected \DateTimeImmutable $updatedAt)
    {
        if ($points < 0 || $points > static::max()) {
            throw InvalidCharacterData::outOfRange(static::field(), $points, 0, static::max());
        }
    }

    abstract public static function max(): int;

    abstract protected static function secondsPerPoint(): int;

    abstract protected static function field(): string;

    abstract protected static function insufficient(int $cost, int $available): \DomainException;

    public static function full(\DateTimeImmutable $now): static
    {
        return new static(static::max(), $now);
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

        return static::secondsPerPoint() - self::secondsBetween($regenerated->updatedAt, $now);
    }

    public function spend(int $cost, \DateTimeImmutable $now): static
    {
        if ($cost < self::MIN_COST) {
            throw InvalidCharacterData::belowMinimum(static::field().'Cost', $cost, self::MIN_COST);
        }

        $regenerated = $this->regenerated($now);
        if ($regenerated->points < $cost) {
            throw static::insufficient($cost, $regenerated->points);
        }

        return new static($regenerated->points - $cost, $regenerated->isFull() ? $now : $regenerated->updatedAt);
    }

    private function regenerated(\DateTimeImmutable $now): static
    {
        $gained = intdiv(self::secondsBetween($this->updatedAt, $now), static::secondsPerPoint());
        if (0 === $gained || $this->isFull()) {
            return $this;
        }

        return new static(
            min(static::max(), $this->points + $gained),
            $this->updatedAt->add(new \DateInterval(\sprintf('PT%dS', $gained * static::secondsPerPoint()))),
        );
    }

    private function isFull(): bool
    {
        return $this->points >= static::max();
    }

    private static function secondsBetween(\DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        return max(0, $to->getTimestamp() - $from->getTimestamp());
    }
}
