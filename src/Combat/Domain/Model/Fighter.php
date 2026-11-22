<?php

declare(strict_types=1);

namespace App\Combat\Domain\Model;

use App\Combat\Domain\Exception\InvalidFighter;

final readonly class Fighter
{
    public const MIN_LEVEL = 1;
    public const MIN_STAT = 1;

    public function __construct(
        private string $name,
        private int $level,
        private int $strength,
        private int $endurance,
        private int $technique,
        private int $charisma,
    ) {
        if ('' === trim($name)) {
            throw InvalidFighter::blankName();
        }
        self::assertAtLeast('level', $level, self::MIN_LEVEL);
        self::assertAtLeast('strength', $strength, self::MIN_STAT);
        self::assertAtLeast('endurance', $endurance, self::MIN_STAT);
        self::assertAtLeast('technique', $technique, self::MIN_STAT);
        self::assertAtLeast('charisma', $charisma, self::MIN_STAT);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function level(): int
    {
        return $this->level;
    }

    public function strength(): int
    {
        return $this->strength;
    }

    public function endurance(): int
    {
        return $this->endurance;
    }

    public function technique(): int
    {
        return $this->technique;
    }

    public function charisma(): int
    {
        return $this->charisma;
    }

    public function maxHp(): int
    {
        return $this->endurance * CombatBalance::HP_PER_ENDURANCE + $this->level * CombatBalance::HP_PER_LEVEL;
    }

    public function baseDamage(): int
    {
        return $this->strength * CombatBalance::DAMAGE_PER_STRENGTH + $this->technique * CombatBalance::DAMAGE_PER_TECHNIQUE;
    }

    /**
     * @return array{name: string, level: int, strength: int, endurance: int, technique: int, charisma: int, maxHp: int}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'level' => $this->level,
            'strength' => $this->strength,
            'endurance' => $this->endurance,
            'technique' => $this->technique,
            'charisma' => $this->charisma,
            'maxHp' => $this->maxHp(),
        ];
    }

    private static function assertAtLeast(string $field, int $value, int $minimum): void
    {
        if ($value < $minimum) {
            throw InvalidFighter::belowMinimum($field, $value, $minimum);
        }
    }
}
