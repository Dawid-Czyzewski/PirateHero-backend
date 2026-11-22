<?php

declare(strict_types=1);

namespace App\Tests\Double\Combat;

use App\Combat\Domain\Random\RandomIntProvider;

final class SequenceRandomIntProvider implements RandomIntProvider
{
    /**
     * @var list<int>
     */
    private array $values;

    public function __construct(int ...$values)
    {
        $this->values = array_values($values);
    }

    public static function always(int $value): self
    {
        return new self($value);
    }

    public function between(int $min, int $max): int
    {
        $value = 1 === \count($this->values) ? $this->values[0] : array_shift($this->values);
        if (null === $value || $value < $min || $value > $max) {
            throw new \LogicException(\sprintf('Scripted roll %s is outside %d..%d.', var_export($value, true), $min, $max));
        }

        return $value;
    }
}
